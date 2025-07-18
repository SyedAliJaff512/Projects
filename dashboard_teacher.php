<?php
require 'session_check.php';

// Only allow teachers to access this dashboard
if ($_SESSION['role'] !== 'teacher') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .dashboard-header {
            text-align: center;
            margin-top: 2rem;
            color:rgb(255, 255, 255);
            font-weight: bold;
            letter-spacing: 1px;
        }
        .container {
            max-width: 800px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 2.5rem 2rem;
        }
        .intro {
            text-align: center;
            margin-bottom: 2rem;
        }
        .teacher-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .teacher-nav a {
            background: #0073e6;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .teacher-nav a:hover, .teacher-nav a:focus {
            background: #004080;
            outline: none;
        }
        .panel-list {
            list-style: disc;
            margin: 1.5rem 0 0 2rem;
            color: #222;
        }
        footer {
            text-align: center;
            padding: 1rem 0;
            background: #f1f6fb;
            color: #555;
            margin-top: 2rem;
            border-top: 1px solid #dde5ef;
            font-size: 1rem;
        }
        @media (max-width: 700px) {
            .container, .intro {
                padding: 1rem;
            }
            .teacher-nav {
                flex-direction: column;
                gap: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1 class="dashboard-header">Welcome, Teacher!</h1>
    </header>
    <nav class="teacher-nav" aria-label="Teacher Navigation">
        <a href="dashboard_teacher.php">Dashboard</a>
        <a href="upload_quiz_assignment.php">Upload Quiz/Assignment</a>
        <a href="mark_attendance.php">Mark Attendance</a>
        <a href="issue_certificate.php">Issue Certificate</a>
        <a href="support_teacher.php">Support</a>
        <a href="logout.php" style="background:#ffeaea; color:#c00;">Logout</a>
    </nav>
    <div class="container">
        <div class="intro">
            <h2>Your Teacher Dashboard</h2>
            <p>
                Manage your teaching responsibilities and interact with your students effectively. Use the navigation above to:
            </p>
            <ul class="panel-list">
                <li>Upload quizzes and assignments for your courses</li>
                <li>Mark student attendance for each session</li>
                <li>Upload recorded video lectures and materials</li>
                <li>Issue certificates to students who complete courses</li>
                <li>Contact support for assistance</li>
            </ul>
        </div>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
