<?php
$pageTitle = 'Register';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    
    // Validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $db = Database::getInstance();
        $emailExists = $db->getRow("SELECT id FROM users WHERE email = ?", [$formData['email']]);
        
        if ($emailExists) {
            $errors['email'] = 'Email is already registered';
        }
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($formData['password'] !== $formData['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match';
    }
    
    // Process form if no errors
    if (empty($errors)) {
        $db = Database::getInstance();
        $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
        
        $result = $db->query(
            "INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())",
            [$formData['name'], $formData['email'], $hashedPassword]
        );
        
        if ($result) {
            $userId = $db->lastInsertId();
            
            // Log the user in
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $formData['name'];
            
            // Redirect to dashboard
            redirect(APP_URL . '/dashboard');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6 md:p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold">Create an Account</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Already have an account? <a href="<?= APP_URL ?>/auth/login.php" class="text-primary hover:underline">Sign in</a></p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-4">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= APP_URL ?>/auth/register.php" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= htmlspecialchars($formData['name']) ?>"
                    class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Enter your full name"
                >
                <?php if (isset($errors['name'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($formData['email']) ?>"
                    class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Enter your email"
                >
                <?php if (isset($errors['email'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Create a password"
                >
                <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= $errors['password'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm Password</label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Confirm your password"
                >
                <?php if (isset($errors['password_confirm'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= $errors['password_confirm'] ?></p>
                <?php endif; ?>
            </div>
            
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors duration-200">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
