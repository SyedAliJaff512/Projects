<?php
// Database connection for courses_managment

require 'config.php';

// Create MySQLi connection using the correct database name
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, 'courses_managment');

// Check connection
if ($conn->connect_errno) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset(DB_CHARSET);

// Set timezone
date_default_timezone_set('Asia/Karachi');
