<?php
require 'session_check.php';
require 'db.php';

// Only allow 'user' role to access registration
if ($_SESSION['role'] !== 'user') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}


$user_id = $_SESSION['user_id'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $dob = $_POST['dob'];
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    // Validate input
    if ($fullname === '' || $dob === '' || $contact === '' || $address === '') {
        $error = "All fields are required.";
    } else {
        // Generate unique student_id (e.g., STU0001)
        $result = $conn->query("SELECT MAX(student_id) AS maxid FROM students");
        $row = $result->fetch_assoc();
        if ($row && $row['maxid']) {
            $maxid = (int)filter_var($row['maxid'], FILTER_SANITIZE_NUMBER_INT);
        } else {
            $maxid = 0;
        }
        $student_id = 'STU' . str_pad($maxid + 1, 4, '0', STR_PAD_LEFT);

        // Prevent duplicate registration
        $check = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "You are already registered as a student.";
        } else {
            $stmt = $conn->prepare("INSERT INTO students (student_id, user_id, fullname, dob, contact, address, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->bind_param("sissss", $student_id, $user_id, $fullname, $dob, $contact, $address);
            if ($stmt->execute()) {
                // Update user role to student
                $conn->query("UPDATE users SET role='student' WHERE user_id=$user_id");
                $_SESSION['role'] = 'student';
                $success = "Registration successful! Your Student ID: <strong>$student_id</strong>. Redirecting to your dashboard...";
                header("Refresh:3; url=dashboard_student.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header><h1>Student Registration</h1></header>
    <nav>
        <a href="dashboard_user.php">Dashboard</a>
        <a href="register_student.php">Register as Student</a>
        <a href="logout.php" style="color:#c00;">Logout</a>
    </nav>
    <div class="container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST" autocomplete="off">
            <label for="fullname">Full Name:</label>
            <input type="text" name="fullname" id="fullname" required>

            <label for="dob">Date of Birth:</label>
            <input type="date" name="dob" id="dob" required>

            <label for="contact">Contact:</label>
            <input type="text" name="contact" id="contact" required>

            <label for="address">Address:</label>
            <input type="text" name="address" id="address" required>

            <button type="submit">Register</button>
        </form>
        <?php endif; ?>
        <p style="text-align:center; margin-top:1rem;">
            <a href="dashboard_user.php" class="btn">Back to User Dashboard</a>
        </p>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System
    </footer>
</body>
</html>
