<?php
$pageTitle = 'Tasks';
$pageHeader = 'Tasks';
$pageDescription = 'Manage your tasks and track progress';

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$view = isset($_GET['view']) ? sanitizeInput($_GET['view']) : 'grid';
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'updated_at';
$order = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'desc';

// Build query
$params = [$userId, $userId];
$query = "
    SELECT t.*, u.name as creator_name, 
    (SELECT COUNT(*) FROM task_collaborators WHERE task_id = t.id) as collaborator_count
    FROM tasks t
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.created_by = ? OR t.id IN (
        SELECT task_id FROM task_collaborators WHERE user_id = ?
    )
";

// Add status filter if provided
if (!empty($status)) {
    $query .= " AND t.status = ?";
    $params[] = $status;
}

// Add search filter if provided
if (!empty($search)) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add sorting
$validSortFields = ['title', 'due_date', 'status', 'updated_at'];
$validOrderValues = ['asc', 'desc'];

if (!in_array($sort, $validSortFields)) {
    $sort = 'updated_at';
}

if (!in_array($order, $validOrderValues)) {
    $order = 'desc';
}

$query .= " ORDER BY t.$sort $order";

// Execute query
$tasks = $db->getRows($query, $params);

$scripts = [APP_URL . '/js/tasks.js'];

include '../includes/header.php';
?>

<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
        <div class="flex space-x-2">
            <a href="<?= APP_URL ?>/dashboard/create_task.php" class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors shadow-soft">
                <i class="fas fa-plus mr-2"></i> New Task
            </a>
            <a href="<?= APP_URL ?>/dashboard/collaboration.php" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-2xl transition-colors shadow-soft">
                <i class="fas fa-users mr-2"></i> Collaborations
            </a>
        </div>
    </div>
    
    <div class="flex flex-col md:flex-row gap-4 md:items-center">
        <div class="relative">
            <form action="<?= APP_URL ?>/dashboard/tasks.php" method="GET">
                <?php if (!empty($status)): ?>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                <?php endif; ?>
                <?php if (!empty($view)): ?>
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                <?php endif; ?>
                <?php if (!empty($sort)): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <?php endif; ?>
                <?php if (!empty($order)): ?>
                    <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                <?php endif; ?>
                
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search tasks..."
                    class="pl-10 pr-4 py-2 w-full md:w-auto border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600"
                >
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 dark:text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </form>
        </div>
        
        <div class="flex items-center space-x-2">
            <!-- Status filter dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-2xl flex items-center justify-between space-x-2 focus:outline-none">
                    <span>Status: <?= !empty($status) ? $status : 'All' ?></span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                
                <div x-show="open" @click.away="open = false" class="absolute mt-2 w-48 bg-white dark:bg-gray-800 rounded-2xl shadow-lg z-10 border border-gray-200 dark:border-gray-700">
                    <a href="<?= APP_URL ?>/dashboard/tasks.php?view=<?= $view ?>&sort=<?= $sort ?>&order=<?= $order ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 <?= empty($status) ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                        All
                    </a>
                    <a href="<?= APP_URL ?>/dashboard/tasks.php?status=To-Do&view=<?= $view ?>&sort=<?= $sort ?>&order=<?= $order ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 <?= $status === 'To-Do' ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                        To-Do
                    </a>
                    <a href="<?= APP_URL ?>/dashboard/tasks.php?status=In Progress&view=<?= $view ?>&sort=<?= $sort ?>&order=<?= $order ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 <?= $status === 'In Progress' ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                        In Progress
                    </a>
                    <a href="<?= APP_URL ?>/dashboard/tasks.php?status=Done&view=<?= $view ?>&sort=<?= $sort ?>&order=<?= $order ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 <?= $status === 'Done' ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                        Done
                    </a>
                </div>
            </div>
            
            <!-- Sort dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-2xl flex items-center justify-between space-x-2 focus:outline-none">
                    <span>Sort</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </button>
                
                <div x-show="open" @click.away="open = false" class="absolute mt-2 w-48 bg-white dark:bg-gray-800 rounded-2xl shadow-lg z-10 border border-gray-200 dark:border-gray-700">
                    <?php
                    $sortOptions = [
                        'updated_at' => 'Last Updated',
                        'due_date' => 'Due Date',
                        'title' => 'Title',
                        'status' => 'Status'
                    ];
                    foreach ($sortOptions as $key => $label): 
                        $newOrder = ($sort === $key && $order === 'asc') ? 'desc' : 'asc';
                        $isActive = $sort === $key;
                        $orderIcon = $order === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down';
                    ?>
                        <a href="<?= APP_URL ?>/dashboard/tasks.php?sort=<?= $key ?>&order=<?= $newOrder ?>&view=<?= $view ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 <?= $isActive ? 'bg-gray-100 dark:bg-gray-700' : '' ?>">
                            <?= $label ?>
                            <?php if ($isActive): ?>
                                <i class="fas <?= $orderIcon ?> ml-2 text-xs"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- View toggle -->
            <div class="flex rounded-2xl border border-gray-300 dark:border-gray-700 overflow-hidden">
                <a href="<?= APP_URL ?>/dashboard/tasks.php?view=grid<?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="px-3 py-2 <?= $view !== 'list' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                    <i class="fas fa-th-large"></i>
                </a>
                <a href="<?= APP_URL ?>/dashboard/tasks.php?view=list<?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>&sort=<?= $sort ?>&order=<?= $order ?>" class="px-3 py-2 <?= $view === 'list' ? 'bg-primary text-white' : 'bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                    <i class="fas fa-list"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (empty($tasks)): ?>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <i class="fas fa-tasks text-gray-400 dark:text-gray-500 text-2xl"></i>
        </div>
        <h3 class="text-lg font-medium mb-2">No tasks found</h3>
        
        <?php if (!empty($search) || !empty($status)): ?>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Try adjusting your filters</p>
            <a href="<?= APP_URL ?>/dashboard/tasks.php" class="inline-block px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                Clear Filters
            </a>
        <?php else: ?>
            <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by creating your first task</p>
            <a href="<?= APP_URL ?>/dashboard/create_task.php" class="inline-block px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                Create Task
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php if ($view === 'list'): ?>
        <!-- List view -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collaborators</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium"><?= htmlspecialchars($task['title']) ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php if ($task['created_by'] == $userId): ?>
                                                <span>You created this task</span>
                                            <?php else: ?>
                                                <span>Created by <?= htmlspecialchars($task['creator_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= getStatusBadge($task['status']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?= formatDate($task['due_date']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($task['collaborator_count'] > 0): ?>
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-full">
                                            <?= $task['collaborator_count'] ?> collaborator<?= $task['collaborator_count'] > 1 ? 's' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">No collaborators</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $task['id'] ?>" class="text-primary hover:text-primary-dark mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($task['created_by'] == $userId): ?>
                                        <button 
                                            class="delete-task text-red-500 hover:text-red-600"
                                            data-task-id="<?= $task['id'] ?>"
                                            data-task-title="<?= htmlspecialchars($task['title']) ?>"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Grid view -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($tasks as $task): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg mb-1"><?= htmlspecialchars($task['title']) ?></h3>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                <?php if ($task['created_by'] == $userId): ?>
                                    <span>You created this task</span>
                                <?php else: ?>
                                    <span>Created by <?= htmlspecialchars($task['creator_name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?= getStatusBadge($task['status']) ?>
                    </div>
                    
                    <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                        <?= nl2br(htmlspecialchars($task['description'])) ?>
                    </p>
                    
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-sm font-medium">Due Date</div>
                            <div class="text-gray-500 dark:text-gray-400"><?= formatDate($task['due_date']) ?></div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="<?= APP_URL ?>/dashboard/edit_task.php?id=<?= $task['id'] ?>" class="p-2 text-primary hover:text-primary-dark">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($task['created_by'] == $userId): ?>
                                <button 
                                    class="delete-task p-2 text-red-500 hover:text-red-600"
                                    data-task-id="<?= $task['id'] ?>"
                                    data-task-title="<?= htmlspecialchars($task['title']) ?>"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($task['collaborator_count'] > 0): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center">
                                <i class="fas fa-users text-gray-400 mr-2"></i>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= $task['collaborator_count'] ?> collaborator<?= $task['collaborator_count'] > 1 ? 's' : '' ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

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

<?php include '../includes/footer.php'; ?>
