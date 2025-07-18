<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set default role if not set (new safety measure)
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'user'; // Default role
}

// Validate user role exists (existing security check)
$allowed_roles = ['user', 'student', 'teacher'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
