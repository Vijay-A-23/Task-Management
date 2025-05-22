<?php
require_once 'db.php';

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to a specific URL
 * @param string $url
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get logged in user details
 * @return array|false
 */
function getUserData() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = Database::getInstance();
    $user = $db->getRow("SELECT id, name, email FROM users WHERE id = ?", 
        [$_SESSION['user_id']]);
    
    return $user;
}

/**
 * Validate and sanitize input
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if a user has permission for a task
 * @param int $taskId
 * @param string $requiredRole
 * @return bool
 */
function hasTaskPermission($taskId, $requiredRole = 'Viewer') {
    if (!isLoggedIn()) {
        return false;
    }
    
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    
    // First check if user is the task creator (has Owner role)
    $task = $db->getRow("SELECT * FROM tasks WHERE id = ? AND created_by = ?",
        [$taskId, $userId]);
    
    if ($task) {
        return true; // User is the owner
    }
    
    // Otherwise check collaborator role
    $roles = ['Owner' => 3, 'Editor' => 2, 'Viewer' => 1];
    $requiredLevel = $roles[$requiredRole];
    
    $collaborator = $db->getRow(
        "SELECT * FROM task_collaborators WHERE task_id = ? AND user_id = ?",
        [$taskId, $userId]
    );
    
    if (!$collaborator) {
        return false;
    }
    
    $userRole = $collaborator['role'];
    $userLevel = $roles[$userRole];
    
    return $userLevel >= $requiredLevel;
}

/**
 * Generate a secure random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get status badge HTML
 * @param string $status
 * @return string
 */
function getStatusBadge($status) {
    $badgeClasses = [
        'To-Do' => 'bg-blue-500/20 text-blue-500',
        'In Progress' => 'bg-yellow-500/20 text-yellow-500',
        'Done' => 'bg-green-500/20 text-green-500'
    ];
    
    $class = $badgeClasses[$status] ?? 'bg-gray-500/20 text-gray-500';
    
    return '<span class="rounded-full px-3 py-1 text-xs font-medium ' . $class . '">' . $status . '</span>';
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    $timestamp = strtotime($date);
    return date('M j, Y', $timestamp);
}

/**
 * Send invitation email
 * @param string $email
 * @param string $taskTitle
 * @param string $inviterName
 * @param string $role
 * @param string $token
 * @return bool
 */
function sendInvitationEmail($email, $taskTitle, $inviterName, $role, $token) {
    $subject = "You've been invited to collaborate on a task";
    
    $acceptUrl = APP_URL . "/dashboard/collaboration.php?action=accept&token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Task Collaboration Invitation</title>
    </head>
    <body>
        <h2>Task Collaboration Invitation</h2>
        <p>Hello,</p>
        <p>{$inviterName} has invited you to collaborate on the task: <strong>{$taskTitle}</strong></p>
        <p>You have been assigned the role of: <strong>{$role}</strong></p>
        <p>To accept this invitation, please click the link below:</p>
        <p><a href='{$acceptUrl}'>Accept Invitation</a></p>
        <p>Thank you,<br>The Task Manager Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . MAIL_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
    
    // Use mail() function for development, consider a more robust mail solution for production
    return mail($email, $subject, $message, $headers);
}
