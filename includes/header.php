<?php 
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$user = null;
if (isLoggedIn()) {
    $user = getUserData();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#9AABFF',
                        'primary-dark': '#8296FF',
                        'primary-light': '#B1BEFF',
                    },
                    borderRadius: {
                        '2xl': '1rem',
                    },
                    boxShadow: {
                        'soft': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= APP_URL ?>/css/custom.css">
    
    <!-- App URL meta tag for JavaScript -->
    <meta name="app-url" content="<?= APP_URL ?>">
    
    <!-- Set dark mode based on localStorage -->
    <script>
        // Check for dark mode preference
        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && 
             window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col transition-colors duration-200">
    <header class="bg-white dark:bg-gray-800 shadow-soft">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="<?= APP_URL ?>" class="flex items-center">
                    <i class="fas fa-tasks text-primary text-2xl mr-2"></i>
                    <span class="text-xl font-bold"><?= APP_NAME ?></span>
                </a>
                
                <div class="flex items-center space-x-4">
                    <button id="darkModeToggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        <i class="fa fa-moon hidden dark:block text-yellow-300"></i>
                        <i class="fa fa-sun block dark:hidden text-yellow-500"></i>
                    </button>
                    
                    <?php if ($user): ?>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none">
                                <div class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-white">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                                <span class="hidden md:block"><?= htmlspecialchars($user['name']) ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-2xl shadow-lg py-2 z-10">
                                <a href="<?= APP_URL ?>/dashboard" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Dashboard
                                </a>
                                <a href="<?= APP_URL ?>/auth/logout.php" class="block px-4 py-2 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= APP_URL ?>/auth/login.php" class="px-4 py-2 rounded-2xl bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Login
                        </a>
                        <a href="<?= APP_URL ?>/auth/register.php" class="px-4 py-2 rounded-2xl bg-primary hover:bg-primary-dark text-white transition-colors">
                            Sign Up
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <main class="flex-grow container mx-auto px-4 py-6">
        <?php if (isset($pageHeader)): ?>
            <div class="mb-6">
                <h1 class="text-2xl md:text-3xl font-bold"><?= $pageHeader ?></h1>
                <?php if (isset($pageDescription)): ?>
                    <p class="text-gray-600 dark:text-gray-400 mt-1"><?= $pageDescription ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
