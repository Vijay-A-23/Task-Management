<?php
$pageTitle = 'Create Task';
$pageHeader = 'Create New Task';
$pageDescription = 'Add a new task to your list';

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect(APP_URL . '/auth/login.php');
}

$errors = [];
$formData = [
    'title' => '',
    'description' => '',
    'due_date' => date('Y-m-d', strtotime('+1 week')),
    'status' => 'To-Do'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'due_date' => sanitizeInput($_POST['due_date'] ?? ''),
        'status' => sanitizeInput($_POST['status'] ?? 'To-Do')
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
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        
        $result = $db->query(
            "INSERT INTO tasks (title, description, due_date, status, created_by, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $formData['title'],
                $formData['description'],
                $formData['due_date'],
                $formData['status'],
                $userId
            ]
        );
        
        if ($result) {
            // Redirect to task list
            redirect(APP_URL . '/dashboard/tasks.php');
        } else {
            $errors['general'] = 'Failed to create task. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6">
        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-4">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= APP_URL ?>/dashboard/create_task.php" class="space-y-6">
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
            
            <div class="flex items-center justify-end space-x-3 pt-4">
                <a href="<?= APP_URL ?>/dashboard/tasks.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-2xl transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors">
                    Create Task
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
