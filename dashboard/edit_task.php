<?php
$pageTitle = 'Edit Task';
$pageHeader = 'Edit Task';

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

// Check if task ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect(APP_URL . '/dashboard/tasks.php');
}

$taskId = (int)$_GET['id'];
$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get task details
$task = $db->getRow(
    "SELECT t.*, u.name as creator_name
     FROM tasks t
     JOIN users u ON t.created_by = u.id
     WHERE t.id = ?",
    [$taskId]
);

// Check if task exists and user has permission to edit
if (!$task || !hasTaskPermission($taskId, $task['created_by'] == $userId ? 'Owner' : 'Editor')) {
    // User doesn't have permission or task doesn't exist
    redirect(APP_URL . '/dashboard/tasks.php');
}

$pageDescription = 'Editing task: ' . $task['title'];

// Get collaborators
$collaborators = $db->getRows(
    "SELECT tc.*, u.name, u.email
     FROM task_collaborators tc
     JOIN users u ON tc.user_id = u.id
     WHERE tc.task_id = ?
     ORDER BY tc.created_at",
    [$taskId]
);

$errors = [];
$formData = [
    'title' => $task['title'],
    'description' => $task['description'],
    'due_date' => $task['due_date'],
    'status' => $task['status']
];

// Handle task update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_task') {
    $formData = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'due_date' => sanitizeInput($_POST['due_date'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? '')
    ];
    
    // Validation
    if (empty($formData['title'])) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($formData['title']) > 100) {
        $errors['title'] = 'Title cannot exceed 100 characters';
    }
    
    if (strlen($formData['description']) > 1000) {
        $errors['description'] = 'Description cannot exceed 1000 characters';
    }
    
    if (empty($formData['due_date'])) {
        $errors['due_date'] = 'Due date is required';
    } elseif (strtotime($formData['due_date']) === false) {
        $errors['due_date'] = 'Invalid date format';
    }
    
    $validStatuses = ['To-Do', 'In Progress', 'Done'];
    if (!in_array($formData['status'], $validStatuses)) {
        $errors['status'] = 'Invalid status';
    }
    
    // Process form if no errors
    if (empty($errors)) {
        $result = $db->query(
            "UPDATE tasks 
             SET title = ?, description = ?, due_date = ?, status = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $formData['title'],
                $formData['description'],
                $formData['due_date'],
                $formData['status'],
                $taskId
            ]
        );
        
        if ($result) {
            redirect(APP_URL . '/dashboard/tasks.php');
        } else {
            $errors['general'] = 'Failed to update task. Please try again.';
        }
    }
}

// Handle invite form submission
$inviteErrors = [];
$inviteFormData = [
    'email' => '',
    'role' => 'Viewer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'invite') {
    // Check if user is the task owner
    if ($task['created_by'] != $userId) {
        $inviteErrors['general'] = 'Only the task owner can invite collaborators';
    } else {
        $inviteFormData = [
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'role' => sanitizeInput($_POST['role'] ?? 'Viewer')
        ];
        
        // Validation
        if (empty($inviteFormData['email'])) {
            $inviteErrors['email'] = 'Email is required';
        } elseif (!filter_var($inviteFormData['email'], FILTER_VALIDATE_EMAIL)) {
            $inviteErrors['email'] = 'Please enter a valid email address';
        } else {
            // Check if user exists
            $invitedUser = $db->getRow("SELECT id, email FROM users WHERE email = ?", 
                [$inviteFormData['email']]);
            
            if ($invitedUser) {
                // Check if user is already a collaborator
                $existingCollaborator = $db->getRow(
                    "SELECT * FROM task_collaborators WHERE task_id = ? AND user_id = ?",
                    [$taskId, $invitedUser['id']]
                );
                
                if ($existingCollaborator) {
                    $inviteErrors['email'] = 'This user is already a collaborator';
                }
                
                // Check if user is the task owner
                if ($invitedUser['id'] == $task['created_by']) {
                    $inviteErrors['email'] = 'Cannot invite the task owner';
                }
            }
        }
        
        $validRoles = ['Viewer', 'Editor', 'Owner'];
        if (!in_array($inviteFormData['role'], $validRoles)) {
            $inviteErrors['role'] = 'Invalid role';
        }
        
        // Process invite if no errors
        if (empty($inviteErrors)) {
            // Check if user exists in the system
            $invitedUser = $db->getRow("SELECT id, name, email FROM users WHERE email = ?", 
                [$inviteFormData['email']]);
            
            if ($invitedUser) {
                // User exists, add as collaborator directly
                $result = $db->query(
                    "INSERT INTO task_collaborators (task_id, user_id, role, created_at) 
                     VALUES (?, ?, ?, NOW())",
                    [$taskId, $invitedUser['id'], $inviteFormData['role']]
                );
                
                if ($result) {
                    // Refresh the page to show updated collaborators
                    redirect(APP_URL . "/dashboard/edit_task.php?id=$taskId&invite_success=1");
                } else {
                    $inviteErrors['general'] = 'Failed to add collaborator. Please try again.';
                }
            } else {
                // User doesn't exist, create invitation
                $token = generateToken();
                
                $result = $db->query(
                    "INSERT INTO invitations (task_id, invited_email, role, token, status, created_at) 
                     VALUES (?, ?, ?, ?, 'pending', NOW())",
                    [$taskId, $inviteFormData['email'], $inviteFormData['role'], $token]
                );
                
                if ($result) {
                    // Send invitation email
                    $user = getUserData();
                    if (sendInvitationEmail($inviteFormData['email'], $task['title'], $user['name'], $inviteFormData['role'], $token)) {
                        redirect(APP_URL . "/dashboard/edit_task.php?id=$taskId&invite_success=1");
                    } else {
                        $inviteErrors['general'] = 'Invitation created but email could not be sent.';
                    }
                } else {
                    $inviteErrors['general'] = 'Failed to create invitation. Please try again.';
                }
            }
        }
    }
}

$scripts = [APP_URL . '/js/tasks.js', APP_URL . '/js/collaborators.js'];

include '../includes/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
            <h2 class="text-xl font-bold mb-6">Task Details</h2>
            
            <?php if (isset($errors['general'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-4">
                    <?= $errors['general'] ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $taskId ?>" class="space-y-6">
                <input type="hidden" name="action" value="update_task">
                
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Task Title <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?= htmlspecialchars($formData['title']) ?>"
                        class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Enter task title"
                        <?= $task['created_by'] != $userId && !hasTaskPermission($taskId, 'Editor') ? 'readonly' : '' ?>
                    >
                    <?php if (isset($errors['title'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= $errors['title'] ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Enter task description"
                        <?= $task['created_by'] != $userId && !hasTaskPermission($taskId, 'Editor') ? 'readonly' : '' ?>
                    ><?= htmlspecialchars($formData['description']) ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= $errors['description'] ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="due_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Due Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="due_date"
                            name="due_date"
                            value="<?= htmlspecialchars($formData['due_date']) ?>"
                            class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            <?= $task['created_by'] != $userId && !hasTaskPermission($taskId, 'Editor') ? 'readonly' : '' ?>
                        >
                        <?php if (isset($errors['due_date'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $errors['due_date'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="status"
                            name="status"
                            class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            <?= $task['created_by'] != $userId && !hasTaskPermission($taskId, 'Editor') ? 'disabled' : '' ?>
                        >
                            <option value="To-Do" <?= $formData['status'] === 'To-Do' ? 'selected' : '' ?>>To-Do</option>
                            <option value="In Progress" <?= $formData['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Done" <?= $formData['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $errors['status'] ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Created by <?= $task['creator_name'] ?> <?= $task['created_by'] == $userId ? '(you)' : '' ?>
                    </p>
                </div>
                
                <div class="flex items-center justify-end space-x-3 pt-4">
                    <a href="<?= APP_URL ?>/dashboard/tasks.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-2xl transition-colors">
                        Cancel
                    </a>
                    <?php if ($task['created_by'] == $userId || hasTaskPermission($taskId, 'Editor')): ?>
                        <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                            Save Changes
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Collaborators</h2>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Invite team members to collaborate on this task.
                </p>
            </div>
            
            <?php if (isset($_GET['invite_success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl mb-4">
                    Invitation sent successfully.
                </div>
            <?php endif; ?>
            
            <?php if ($task['created_by'] == $userId): ?>
                <?php if (isset($inviteErrors['general'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-4">
                        <?= $inviteErrors['general'] ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $taskId ?>" class="mb-6 space-y-4">
                    <input type="hidden" name="action" value="invite">
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($inviteFormData['email']) ?>"
                            class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Enter email address"
                        >
                        <?php if (isset($inviteErrors['email'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $inviteErrors['email'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Role
                        </label>
                        <select
                            id="role"
                            name="role"
                            class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="Viewer" <?= $inviteFormData['role'] === 'Viewer' ? 'selected' : '' ?>>Viewer (can only view)</option>
                            <option value="Editor" <?= $inviteFormData['role'] === 'Editor' ? 'selected' : '' ?>>Editor (can edit)</option>
                            <option value="Owner" <?= $inviteFormData['role'] === 'Owner' ? 'selected' : '' ?>>Owner (full access)</option>
                        </select>
                        <?php if (isset($inviteErrors['role'])): ?>
                            <p class="text-red-500 text-xs mt-1"><?= $inviteErrors['role'] ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                        Invite Collaborator
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="space-y-3">
                <div class="flex items-center py-2">
                    <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white mr-3">
                        <?= strtoupper(substr($task['creator_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="font-medium"><?= htmlspecialchars($task['creator_name']) ?> <?= $task['created_by'] == $userId ? '(you)' : '' ?></div>
                        <div class="text-xs bg-purple-500/20 text-purple-500 px-2 py-0.5 rounded-full inline-block">
                            Owner
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($collaborators)): ?>
                    <?php foreach ($collaborators as $collaborator): ?>
                        <div class="flex items-center justify-between py-2">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center text-gray-700 dark:text-gray-300 mr-3">
                                    <?= strtoupper(substr($collaborator['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="font-medium"><?= htmlspecialchars($collaborator['name']) ?> <?= $collaborator['user_id'] == $userId ? '(you)' : '' ?></div>
                                    <div class="text-xs 
                                        <?php 
                                        if ($collaborator['role'] === 'Owner') echo 'bg-purple-500/20 text-purple-500';
                                        elseif ($collaborator['role'] === 'Editor') echo 'bg-blue-500/20 text-blue-500';
                                        else echo 'bg-gray-500/20 text-gray-500';
                                        ?> 
                                        px-2 py-0.5 rounded-full inline-block">
                                        <?= $collaborator['role'] ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($task['created_by'] == $userId): ?>
                                <button
                                    class="remove-collaborator text-red-500 hover:text-red-600"
                                    data-collaborator-id="<?= $collaborator['id'] ?>"
                                    data-collaborator-name="<?= htmlspecialchars($collaborator['name']) ?>"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3 text-gray-500 dark:text-gray-400">
                        No collaborators yet
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($task['created_by'] == $userId): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
                <h2 class="text-xl font-bold mb-4 text-red-500">Danger Zone</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Permanently delete this task and all associated data.
                </p>
                <button
                    id="deleteTaskBtn"
                    class="w-full py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded-2xl transition-colors"
                    data-task-id="<?= $taskId ?>"
                    data-task-title="<?= htmlspecialchars($task['title']) ?>"
                >
                    Delete Task
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Task Confirmation Modal -->
<div id="deleteTaskModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Delete Task</h3>
        <p class="mb-6">Are you sure you want to delete the task "<span id="deleteTaskName"></span>"? This action cannot be undone.</p>
        <div class="flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-2xl transition-colors">
                Cancel
            </button>
            <button id="confirmDelete" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-2xl transition-colors">
                Delete
            </button>
        </div>
    </div>
</div>

<!-- Remove Collaborator Confirmation Modal -->
<div id="removeCollaboratorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Remove Collaborator</h3>
        <p class="mb-6">Are you sure you want to remove "<span id="removeCollaboratorName"></span>" from this task?</p>
        <div class="flex justify-end space-x-3">
            <button id="cancelRemove" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-2xl transition-colors">
                Cancel
            </button>
            <button id="confirmRemove" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-2xl transition-colors">
                Remove
            </button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
