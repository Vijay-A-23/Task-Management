<?php
// Application configuration settings
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'task_manager');

// Application settings
define('APP_NAME', 'Task Manager');
define('APP_URL', 'http://localhost/CollaborativeTaskManager');

// Email settings
define('MAIL_FROM', 'noreply@taskmanager.com');
define('MAIL_NAME', 'Task Manager');

// Error reporting - turn off for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');

// Base path
define('BASE_PATH', __DIR__ . '/../');
