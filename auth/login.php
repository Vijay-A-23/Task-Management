<?php
$pageTitle = 'Login';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

$errors = [];
$formData = [
    'email' => '',
    'password' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? ''
    ];
    
    // Validation
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required';
    }
    
    // Process form if no errors
    if (empty($errors)) {
        $db = Database::getInstance();
        $user = $db->getRow("SELECT id, name, email, password_hash FROM users WHERE email = ?", 
            [$formData['email']]);
        
        if ($user && password_verify($formData['password'], $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Update last login
            $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            // Redirect to dashboard
            redirect(APP_URL . '/dashboard');
        } else {
            $errors['general'] = 'Invalid email or password';
        }
    }
}

include '../includes/header.php';
?>

<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-soft p-6 md:p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold">Sign In</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Don't have an account? <a href="<?= APP_URL ?>/auth/register.php" class="text-primary hover:underline">Sign up</a></p>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-2xl mb-4">
                <?= $errors['general'] ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= APP_URL ?>/auth/login.php" class="space-y-4">
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
                <div class="flex justify-between items-center mb-1">
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <!-- Password reset functionality would go here in a real app -->
                    <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-2 border rounded-2xl focus:ring-primary focus:border-primary dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Enter your password"
                >
                <?php if (isset($errors['password'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?= $errors['password'] ?></p>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                >
                <label for="remember" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                    Remember me
                </label>
            </div>
            
            <div>
                <button type="submit" class="w-full py-2 px-4 bg-primary hover:bg-primary-dark text-white rounded-2xl transition-colors duration-200">
                    Sign In
                </button>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
