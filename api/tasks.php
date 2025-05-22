<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$response = ['success' => false];

// Handle different actions based on request method and parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete') {
        // Delete task
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        
        // Check if task exists and user is the owner
        $task = $db->getRow("SELECT * FROM tasks WHERE id = ? AND created_by = ?", 
            [$taskId, $userId]);
        
        if (!$task) {
            $response = ['success' => false, 'message' => 'Task not found or you do not have permission to delete it'];
        } else {
            // Begin transaction
            $db->getConnection()->beginTransaction();
            
            try {
                // Delete associated collaborators
                $db->query("DELETE FROM task_collaborators WHERE task_id = ?", [$taskId]);
                
                // Delete associated invitations
                $db->query("DELETE FROM invitations WHERE task_id = ?", [$taskId]);
                
                // Delete the task
                $db->query("DELETE FROM tasks WHERE id = ?", [$taskId]);
                
                // Commit transaction
                $db->getConnection()->commit();
                
                $response = ['success' => true];
            } catch (Exception $e) {
                // Rollback on error
                $db->getConnection()->rollBack();
                $response = ['success' => false, 'message' => 'Failed to delete task'];
            }
        }
    } elseif ($action === 'update_status') {
        // Update task status
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        $status = sanitizeInput($_POST['status'] ?? '');
        
        $validStatuses = ['To-Do', 'In Progress', 'Done'];
        if (!in_array($status, $validStatuses)) {
            $response = ['success' => false, 'message' => 'Invalid status'];
        } else {
            // Check if user has permission to update this task
            if (hasTaskPermission($taskId, 'Editor')) {
                $result = $db->query(
                    "UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?",
                    [$status, $taskId]
                );
                
                if ($result) {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update task status'];
                }
            } else {
                $response = ['success' => false, 'message' => 'You do not have permission to update this task'];
            }
        }
    } elseif ($action === 'poll_updates') {
        // Poll for task updates (for real-time simulation)
        $lastUpdate = isset($_POST['last_update']) ? sanitizeInput($_POST['last_update']) : null;
        $taskIds = isset($_POST['task_ids']) ? json_decode($_POST['task_ids'], true) : [];
        
        if (empty($taskIds)) {
            $response = ['success' => false, 'message' => 'No task IDs provided'];
        } else {
            // Validate task IDs
            $taskIds = array_filter($taskIds, 'is_numeric');
            
            if (empty($taskIds)) {
                $response = ['success' => false, 'message' => 'Invalid task IDs'];
            } else {
                // Build placeholders for SQL query
                $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
                
                // Get tasks that have been updated since the last poll
                $params = $taskIds;
                $sql = "SELECT id, title, description, status, due_date, updated_at 
                        FROM tasks WHERE id IN ($placeholders)";
                
                if ($lastUpdate) {
                    $sql .= " AND updated_at > ?";
                    $params[] = $lastUpdate;
                }
                
                $updatedTasks = $db->getRows($sql, $params);
                
                // Get current timestamp for the next poll
                $currentTimestamp = date('Y-m-d H:i:s');
                
                $response = [
                    'success' => true,
                    'tasks' => $updatedTasks,
                    'timestamp' => $currentTimestamp
                ];
            }
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method'];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
