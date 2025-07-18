<?php
// Error reporting for development environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log/php_errors.log');

// Simple local development configuration
define('DB_HOST', '127.0.0.1'); // Use IP instead of hostname
define('DB_USER', 'root');       // Default local user
define('DB_PASS', '');           // Empty password for local
define('DB_NAME', 'courses_managment');
define('DB_CHARSET', 'utf8mb4');



// Application constants
defined('SITE_NAME') || define('SITE_NAME', 'E-Courses System');
defined('BASE_URL') || define('BASE_URL', 'http://localhost/ecourses/');
// Security headers (compatible with your transport app security practices)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
