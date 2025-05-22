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
    
    if ($action === 'remove') {
        // Remove a collaborator from a task
        $collaboratorId = isset($_POST['collaborator_id']) ? (int)$_POST['collaborator_id'] : 0;
        
        // Get collaborator and task details
        $collaborator = $db->getRow(
            "SELECT tc.*, t.created_by as task_owner 
             FROM task_collaborators tc
             JOIN tasks t ON tc.task_id = t.id
             WHERE tc.id = ?",
            [$collaboratorId]
        );
        
        if (!$collaborator) {
            $response = ['success' => false, 'message' => 'Collaborator not found'];
        } elseif ($collaborator['task_owner'] != $userId) {
            $response = ['success' => false, 'message' => 'You do not have permission to remove this collaborator'];
        } else {
            // Remove the collaborator
            $result = $db->query("DELETE FROM task_collaborators WHERE id = ?", [$collaboratorId]);
            
            if ($result) {
                $response = ['success' => true];
            } else {
                $response = ['success' => false, 'message' => 'Failed to remove collaborator'];
            }
        }
    } elseif ($action === 'update_role') {
        // Update a collaborator's role
        $collaboratorId = isset($_POST['collaborator_id']) ? (int)$_POST['collaborator_id'] : 0;
        $role = sanitizeInput($_POST['role'] ?? '');
        
        $validRoles = ['Viewer', 'Editor', 'Owner'];
        if (!in_array($role, $validRoles)) {
            $response = ['success' => false, 'message' => 'Invalid role'];
        } else {
            // Get collaborator and task details
            $collaborator = $db->getRow(
                "SELECT tc.*, t.created_by as task_owner 
                 FROM task_collaborators tc
                 JOIN tasks t ON tc.task_id = t.id
                 WHERE tc.id = ?",
                [$collaboratorId]
            );
            
            if (!$collaborator) {
                $response = ['success' => false, 'message' => 'Collaborator not found'];
            } elseif ($collaborator['task_owner'] != $userId) {
                $response = ['success' => false, 'message' => 'You do not have permission to update this collaborator'];
            } else {
                // Update the collaborator's role
                $result = $db->query(
                    "UPDATE task_collaborators SET role = ? WHERE id = ?",
                    [$role, $collaboratorId]
                );
                
                if ($result) {
                    $response = ['success' => true];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update collaborator role'];
                }
            }
        }
    } elseif ($action === 'get_collaborators') {
        // Get collaborators for a task
        $taskId = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
        
        // Check if user has permission to view this task's collaborators
        if (hasTaskPermission($taskId, 'Viewer')) {
            $collaborators = $db->getRows(
                "SELECT tc.*, u.name, u.email 
                 FROM task_collaborators tc
                 JOIN users u ON tc.user_id = u.id
                 WHERE tc.task_id = ?
                 ORDER BY tc.created_at",
                [$taskId]
            );
            
            $response = ['success' => true, 'collaborators' => $collaborators];
        } else {
            $response = ['success' => false, 'message' => 'You do not have permission to view this task'];
        }
    }
} else {
    $response = ['success' => false, 'message' => 'Invalid request method'];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
