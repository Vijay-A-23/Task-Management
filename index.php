<?php
$pageTitle = 'Home';
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard');
}

include 'includes/header.php';
?>

<div class="flex flex-col md:flex-row items-center justify-between max-w-6xl mx-auto">
    <div class="md:w-1/2 md:pr-8 mb-8 md:mb-0">
        <h1 class="text-4xl md:text-5xl font-bold mb-4 leading-tight">
            Manage tasks <span class="text-primary">collaboratively</span> with your team
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-400 mb-6">
            A simple, powerful task management application that helps you organize work, track progress, and collaborate effectively.
        </p>
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="<?= APP_URL ?>/auth/register.php" class="inline-block px-6 py-3 rounded-2xl bg-primary hover:bg-primary-dark text-white transition-all duration-200 text-center shadow-soft">
                Get Started <i class="fas fa-arrow-right ml-2"></i>
            </a>
            <a href="<?= APP_URL ?>/auth/login.php" class="inline-block px-6 py-3 rounded-2xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 text-center">
                Sign In
            </a>
        </div>
    </div>
    
    <div class="md:w-1/2">
        <img src="https://pixabay.com/get/g876f3209a82132bd33f3a942304003ce4d901639a5ac29f987b23d9f6b1bf8d790d55ec81c91f9c68a66dd9ce13cace79fceb99f688727fba131f57ae8e8b184_1280.jpg" 
             alt="Task Management Collaboration" 
             class="rounded-2xl shadow-soft w-full">
    </div>
</div>

<div class="max-w-6xl mx-auto mt-20">
    <h2 class="text-3xl font-bold text-center mb-12">Key Features</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center mb-4">
                <i class="fas fa-tasks text-primary text-xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Task Management</h3>
            <p class="text-gray-600 dark:text-gray-400">
                Create, organize, and track tasks easily with status updates and due dates.
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center mb-4">
                <i class="fas fa-users text-primary text-xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Team Collaboration</h3>
            <p class="text-gray-600 dark:text-gray-400">
                Invite team members to collaborate on tasks with different permission levels.
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-soft">
            <div class="w-12 h-12 rounded-full bg-primary/20 flex items-center justify-center mb-4">
                <i class="fas fa-chart-bar text-primary text-xl"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Dashboard & Filters</h3>
            <p class="text-gray-600 dark:text-gray-400">
                Get an overview of your tasks with powerful filtering and search capabilities.
            </p>
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto mt-20">
    <div class="flex flex-col md:flex-row items-center bg-white dark:bg-gray-800 rounded-2xl shadow-soft overflow-hidden">
        <div class="md:w-1/2 p-8">
            <h2 class="text-3xl font-bold mb-4">Seamless Collaboration</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Work together with your team members in real-time. Assign tasks, track progress, and achieve your goals together.
            </p>
            <ul class="space-y-2">
                <li class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Invite team members via email</span>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Role-based permissions</span>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    <span>Real-time updates</span>
                </li>
            </ul>
        </div>
        <div class="md:w-1/2">
            <img src="https://pixabay.com/get/g5acf0d27f6c5bc8aaa84a4106b2135f3e849a8486b04544c410c468e5f598a583bd2b996d7053820af4ca986e963fe411de8028608ecb41ce9aa6c8ad372e07e_1280.jpg" 
                 alt="Team Collaboration" 
                 class="w-full h-full object-cover">
        </div>
    </div>
</div>

<div class="max-w-6xl mx-auto mt-20 mb-12 text-center">
    <h2 class="text-3xl font-bold mb-6">Ready to get started?</h2>
    <p class="text-xl text-gray-600 dark:text-gray-400 mb-8 max-w-2xl mx-auto">
        Join thousands of teams who use our task management system to collaborate effectively and get things done.
    </p>
    <a href="<?= APP_URL ?>/auth/register.php" class="inline-block px-8 py-4 rounded-2xl bg-primary hover:bg-primary-dark text-white transition-all duration-200 text-center shadow-soft text-lg">
        Create Your Free Account
    </a>
</div>

<?php include 'includes/footer.php'; ?>
