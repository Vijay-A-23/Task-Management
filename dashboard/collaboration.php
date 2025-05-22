<?php
$pageTitle = 'Collaborations';
$pageHeader = 'Collaborations';
$pageDescription = 'Manage your task collaborations';

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Handle invitation acceptance via URL
$message = '';
if (isset($_GET['action']) && $_GET['action'] === 'accept' && isset($_GET['token'])) {
    $token = sanitizeInput($_GET['token']);
    
    // Check if invitation exists and is pending
    $invitation = $db->getRow(
        "SELECT i.*, t.title as task_title, u.name as inviter_name 
         FROM invitations i
         JOIN tasks t ON i.task_id = t.id
         JOIN users u ON t.created_by = u.id
         WHERE i.token = ? AND i.status = 'pending'",
        [$token]
    );
    
    if ($invitation) {
        // Get user email
        $user = getUserData();
        
        if ($user['email'] === $invitation['invited_email']) {
            // Add user as collaborator
            $result = $db->query(
                "INSERT INTO task_collaborators (task_id, user_id, role, created_at) 
                 VALUES (?, ?, ?, NOW())",
                [$invitation['task_id'], $userId, $invitation['role']]
            );
            
            if ($result) {
                // Update invitation status
                $db->query(
                    "UPDATE invitations SET status = 'accepted' WHERE id = ?",
                    [$invitation['id']]
                );
                
                $message = 'You have successfully joined the task "' . htmlspecialchars($invitation['task_title']) . '" as a ' . $invitation['role'] . '.';
            } else {
                $message = 'Failed to accept invitation. Please try again.';
            }
        } else {
            $message = 'This invitation was sent to a different email address.';
        }
    } else {
        $message = 'The invitation is invalid or has already been accepted.';
    }
}

// Get tasks where user is a collaborator
$collaboratedTasks = $db->getRows(
    "SELECT t.*, u.name as creator_name, tc.role
     FROM tasks t
     JOIN users u ON t.created_by = u.id
     JOIN task_collaborators tc ON t.id = tc.task_id
     WHERE tc.user_id = ? AND t.created_by != ?
     ORDER BY t.updated_at DESC",
    [$userId, $userId]
);

// Get pending invitations
$pendingInvitations = $db->getRows(
    "SELECT i.*, t.title as task_title, u.name as inviter_name
     FROM invitations i
     JOIN tasks t ON i.task_id = t.id
     JOIN users u ON t.created_by = u.id
     WHERE i.invited_email = (SELECT email FROM users WHERE id = ?)
     AND i.status = 'pending'",
    [$userId]
);

// Get sent invitations
$sentInvitations = $db->getRows(
    "SELECT i.*, t.title as task_title
     FROM invitations i
     JOIN tasks t ON i.task_id = t.id
     WHERE t.created_by = ?
     ORDER BY i.created_at DESC",
    [$userId]
);

$scripts = [APP_URL . '/js/tasks.js'];

include '../includes/header.php';
?>

<?php if (!empty($message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-2xl mb-6">
        <?= $message ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Tasks where user is a collaborator -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
            <h2 class="text-xl font-bold mb-6">Tasks You're Collaborating On</h2>
            
            <?php if (empty($collaboratedTasks)): ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <i class="fas fa-users text-gray-400 dark:text-gray-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium mb-2">No collaborations yet</h3>
                    <p class="text-gray-500 dark:text-gray-400">You're not collaborating on any tasks yet</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Your Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($collaboratedTasks as $task): ?>
                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-4 py-4">
                                        <div class="font-medium"><?= htmlspecialchars($task['title']) ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= htmlspecialchars($task['creator_name']) ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="text-xs 
                                            <?php 
                                            if ($task['role'] === 'Owner') echo 'bg-purple-500/20 text-purple-500';
                                            elseif ($task['role'] === 'Editor') echo 'bg-blue-500/20 text-blue-500';
                                            else echo 'bg-gray-500/20 text-gray-500';
                                            ?> 
                                            px-2 py-1 rounded-full">
                                            <?= $task['role'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= getStatusBadge($task['status']) ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= formatDate($task['due_date']) ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $task['id'] ?>" class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sent invitations -->
        <?php if (!empty($sentInvitations)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
                <h2 class="text-xl font-bold mb-6">Invitations You've Sent</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Invited Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date Sent</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sentInvitations as $invitation): ?>
                                <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                    <td class="px-4 py-4">
                                        <div class="font-medium"><?= htmlspecialchars($invitation['task_title']) ?></div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= htmlspecialchars($invitation['invited_email']) ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="text-xs 
                                            <?php 
                                            if ($invitation['role'] === 'Owner') echo 'bg-purple-500/20 text-purple-500';
                                            elseif ($invitation['role'] === 'Editor') echo 'bg-blue-500/20 text-blue-500';
                                            else echo 'bg-gray-500/20 text-gray-500';
                                            ?> 
                                            px-2 py-1 rounded-full">
                                            <?= $invitation['role'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="text-xs <?= $invitation['status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-500' : 'bg-green-500/20 text-green-500' ?> px-2 py-1 rounded-full">
                                            <?= ucfirst($invitation['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?= formatDate($invitation['created_at']) ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <?php if ($invitation['status'] === 'pending'): ?>
                                            <button 
                                                class="cancel-invitation text-red-500 hover:text-red-600"
                                                data-invitation-id="<?= $invitation['id'] ?>"
                                                data-email="<?= htmlspecialchars($invitation['invited_email']) ?>"
                                            >
                                                <i class="fas fa-times mr-1"></i> Cancel
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400">Accepted</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div>
        <!-- Pending invitations -->
        <?php if (!empty($pendingInvitations)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Pending Invitations</h2>
                <div class="space-y-4">
                    <?php foreach ($pendingInvitations as $invitation): ?>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
                            <div class="font-medium mb-1"><?= htmlspecialchars($invitation['task_title']) ?></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                Invited by <?= htmlspecialchars($invitation['inviter_name']) ?> as <?= $invitation['role'] ?>
                            </div>
                            <div class="flex space-x-2">
                                <button
                                    class="respond-invite px-3 py-1 bg-primary hover:bg-primary-dark text-white rounded-xl text-sm transition-colors"
                                    data-invitation-id="<?= $invitation['id'] ?>"
                                    data-action="accept"
                                >
                                    Accept
                                </button>
                                <button
                                    class="respond-invite px-3 py-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-650 rounded-xl text-sm transition-colors"
                                    data-invitation-id="<?= $invitation['id'] ?>"
                                    data-action="decline"
                                >
                                    Decline
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick access -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="<?= APP_URL ?>/dashboard/create_task.php" class="block w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors text-center">
                    <i class="fas fa-plus mr-2"></i> Create New Task
                </a>
                <a href="<?= APP_URL ?>/dashboard/tasks.php" class="block w-full py-2 px-4 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-650 rounded-2xl transition-colors text-center">
                    <i class="fas fa-tasks mr-2"></i> View All Tasks
                </a>
            </div>
            
            <div class="mt-6">
                <h3 class="font-medium text-lg mb-2">About Collaboration</h3>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <p>
                        <i class="fas fa-eye mr-2 text-gray-500"></i>
                        <strong>Viewers</strong> can only view task details
                    </p>
                    <p>
                        <i class="fas fa-edit mr-2 text-blue-500"></i>
                        <strong>Editors</strong> can modify task details
                    </p>
                    <p>
                        <i class="fas fa-user-shield mr-2 text-purple-500"></i>
                        <strong>Owners</strong> have full control over tasks
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Invitation Confirmation Modal -->
<div id="cancelInvitationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Cancel Invitation</h3>
        <p class="mb-6">Are you sure you want to cancel the invitation sent to <span id="invitationEmail" class="font-medium"></span>?</p>
        <div class="flex justify-end space-x-3">
            <button id="cancelInvitationBtn" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-2xl transition-colors">
                Go Back
            </button>
            <button id="confirmCancelInvitation" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-2xl transition-colors">
                Cancel Invitation
            </button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle cancel invitation buttons
        document.querySelectorAll('.cancel-invitation').forEach(button => {
            button.addEventListener('click', function() {
                const invitationId = this.getAttribute('data-invitation-id');
                const email = this.getAttribute('data-email');
                
                // Show confirmation modal
                const modal = document.getElementById('cancelInvitationModal');
                document.getElementById('invitationEmail').textContent = email;
                modal.classList.remove('hidden');
                
                // Handle confirmation
                document.getElementById('confirmCancelInvitation').onclick = function() {
                    // Send AJAX request to cancel invitation
                    fetch(`${APP_URL}/api/invites.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=cancel&invitation_id=${invitationId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Failed to cancel invitation: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                };
                
                // Handle cancel button
                document.getElementById('cancelInvitationBtn').onclick = function() {
                    modal.classList.add('hidden');
                };
            });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
