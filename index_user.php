<?php
require 'session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <h1>Welcome to Designer's Hub</h1>
    </header>
    <nav>
        <a href="dashboard_user.php">Dashboard</a>
        <a href="index_user.php">About Us</a>
        <a href="courses_user.php">Courses</a>
        <a href="payment_user.php">Payment</a>
        <a href="support_user.php">Support</a>
        <a href="register_student.php">Register as Student</a>
        <a href="register_teacher.php">Register as Teacher</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php" style="color:#c00;">Logout</a>
        <?php endif; ?>
    </nav>
    <div class="container">
        <section class="role-panel-user">
            <h2>About Our Institute</h2>
            <p>
                <strong>Designer's Hub</strong> is dedicated to providing high-quality online and on-site education for learners of all backgrounds. 
                Our experienced instructors, interactive courses, and supportive environment help students achieve their academic and professional goals.
            </p>
            <ul>
                <li>Wide range of certified courses</li>
                <li>Flexible learning modes (Online & On-Site)</li>
                <li>Secure and simple payment options</li>
                <li>Dedicated student and teacher support</li>
            </ul>
            <p>
                Join us to enhance your skills and advance your career with recognized certifications.
            </p>
        </section>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses Institute. All rights reserved.
    </footer>
</body>
</html>
