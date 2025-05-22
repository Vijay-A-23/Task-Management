<?php
$pageTitle = 'Dashboard';
$pageHeader = 'Dashboard';
$pageDescription = 'Overview of your tasks and activities';

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get task counts by status
$statusCounts = [
    'Total' => 0,
    'To-Do' => 0,
    'In Progress' => 0,
    'Done' => 0
];

// Get tasks created by user
$createdTasks = $db->getRows(
    "SELECT status, COUNT(*) as count FROM tasks WHERE created_by = ? GROUP BY status",
    [$userId]
);

foreach ($createdTasks as $task) {
    $statusCounts[$task['status']] = (int)$task['count'];
    $statusCounts['Total'] += (int)$task['count'];
}

// Get collaborated tasks
$collaboratedTasks = $db->getRows(
    "SELECT t.status, COUNT(*) as count 
     FROM tasks t
     JOIN task_collaborators tc ON t.id = tc.task_id
     WHERE tc.user_id = ? AND t.created_by != ?
     GROUP BY t.status",
    [$userId, $userId]
);

foreach ($collaboratedTasks as $task) {
    $statusCounts[$task['status']] += (int)$task['count'];
    $statusCounts['Total'] += (int)$task['count'];
}

// Get recent tasks (both created and collaborated)
$recentTasks = $db->getRows(
    "SELECT t.*, u.name as creator_name, 
     (SELECT COUNT(*) FROM task_collaborators WHERE task_id = t.id) as collaborator_count
     FROM tasks t
     LEFT JOIN users u ON t.created_by = u.id
     WHERE t.created_by = ? OR t.id IN (
         SELECT task_id FROM task_collaborators WHERE user_id = ?
     )
     ORDER BY t.updated_at DESC
     LIMIT 5",
    [$userId, $userId]
);

// Get invitations
$pendingInvitations = $db->getRows(
    "SELECT i.*, t.title as task_title, u.name as inviter_name
     FROM invitations i
     JOIN tasks t ON i.task_id = t.id
     JOIN users u ON t.created_by = u.id
     WHERE i.invited_email = (SELECT email FROM users WHERE id = ?)
     AND i.status = 'pending'",
    [$userId]
);

// Get recent collaborators
$recentCollaborators = $db->getRows(
    "SELECT DISTINCT u.id, u.name, u.email
     FROM users u
     JOIN task_collaborators tc ON u.id = tc.user_id
     JOIN tasks t ON tc.task_id = t.id
     WHERE t.created_by = ? AND u.id != ?
     ORDER BY tc.created_at DESC
     LIMIT 5",
    [$userId, $userId]
);

$scripts = [APP_URL . '/js/tasks.js'];

include '../includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium">Total Tasks</h3>
            <span class="text-primary"><i class="fas fa-tasks"></i></span>
        </div>
        <p class="text-3xl font-bold"><?= $statusCounts['Total'] ?></p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
            <a href="<?= APP_URL ?>/dashboard/tasks.php" class="text-primary hover:underline">View all tasks</a>
        </p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium">To-Do</h3>
            <span class="text-blue-500"><i class="fas fa-clipboard-list"></i></span>
        </div>
        <p class="text-3xl font-bold"><?= $statusCounts['To-Do'] ?></p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
            <a href="<?= APP_URL ?>/dashboard/tasks.php?status=To-Do" class="text-primary hover:underline">View to-do tasks</a>
        </p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium">In Progress</h3>
            <span class="text-yellow-500"><i class="fas fa-spinner"></i></span>
        </div>
        <p class="text-3xl font-bold"><?= $statusCounts['In Progress'] ?></p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
            <a href="<?= APP_URL ?>/dashboard/tasks.php?status=In Progress" class="text-primary hover:underline">View in-progress tasks</a>
        </p>
    </div>
    
    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-medium">Completed</h3>
            <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
        </div>
        <p class="text-3xl font-bold"><?= $statusCounts['Done'] ?></p>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
            <a href="<?= APP_URL ?>/dashboard/tasks.php?status=Done" class="text-primary hover:underline">View completed tasks</a>
        </p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">Recent Tasks</h2>
                <a href="<?= APP_URL ?>/dashboard/tasks.php" class="text-sm text-primary hover:underline">View all</a>
            </div>
            
            <?php if (empty($recentTasks)): ?>
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <i class="fas fa-tasks text-gray-400 dark:text-gray-500 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium mb-2">No tasks yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by creating your first task</p>
                    <a href="<?= APP_URL ?>/dashboard/create_task.php" class="inline-block px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                        Create Task
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTasks as $task): ?>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="font-medium"><?= htmlspecialchars($task['title']) ?></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    <?php if ($task['created_by'] == $userId): ?>
                                                        <span>You created this task</span>
                                                    <?php else: ?>
                                                        <span>Created by <?= htmlspecialchars($task['creator_name']) ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task['collaborator_count'] > 0): ?>
                                                        <span class="ml-2 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-full">
                                                            <?= $task['collaborator_count'] ?> collaborator<?= $task['collaborator_count'] > 1 ? 's' : '' ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <?= getStatusBadge($task['status']) ?>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap">
                                            <?= formatDate($task['due_date']) ?>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap text-sm">
                                            <a href="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $task['id'] ?>" class="text-primary hover:text-primary-dark mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="<?= APP_URL ?>/dashboard/create_task.php" class="block w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors text-center">
                    <i class="fas fa-plus mr-2"></i> Create New Task
                </a>
                <a href="<?= APP_URL ?>/dashboard/collaboration.php" class="block w-full py-2 px-4 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-650 rounded-2xl transition-colors text-center">
                    <i class="fas fa-users mr-2"></i> View Collaborations
                </a>
            </div>
        </div>
        
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
        
        <?php if (!empty($recentCollaborators)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
                <h2 class="text-xl font-bold mb-4">Recent Collaborators</h2>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($recentCollaborators as $collaborator): ?>
                        <div class="py-3 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white mr-3">
                                <?= strtoupper(substr($collaborator['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="font-medium"><?= htmlspecialchars($collaborator['name']) ?></div>
                                <div class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($collaborator['email']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
