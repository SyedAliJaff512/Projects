<?php
require 'session_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | Designer's Hub System</title>
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
        .options {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2.5rem 0;
        }
        .btn {
            background: #0073e6;
            color: #fff;
            padding: 1rem 2rem;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
            display: inline-block;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .btn:hover, .btn:focus {
            background: #004080;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            outline: none;
        }
        .container {
            max-width: 700px;
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
        nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1.2rem;
            background: #f1f6fb;
            padding: 1rem 0;
            border-bottom: 1px solid #dde5ef;
            margin-bottom: 2rem;
        }
        nav a {
            color: #004080;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            transition: background 0.2s, color 0.2s;
        }
        nav a:hover, nav a:focus {
            background: #0073e6;
            color: #fff;
            outline: none;
        }
        nav a[style*="color:#c00"] {
            color: #c00 !important;
            background: #ffeaea;
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
            .options {
                flex-direction: column;
                gap: 1rem;
            }
            nav {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1 class="dashboard-header">Welcome, User!</h1>
        <pre><b>Project Developers:</b>Syed Muhammad ali
                Sanaila Sajjad
                Rameesha Ilyas</pre>
    </header>
    <nav aria-label="Main Navigation">
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
        <div class="intro">
            <h2>About E-Courses Institute</h2>
            <p>
                E-Courses Institute is your gateway to quality online and on-site education. 
                Explore a variety of certified courses, flexible learning modes, and supportive resources to help you succeed.
            </p>
        </div>
        <div class="options">
            <a href="register_student.php" class="btn" aria-label="Register as Student">Become Student</a>
            <a href="register_teacher.php" class="btn" aria-label="Register as Teacher">Become Teacher</a>
        </div>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
