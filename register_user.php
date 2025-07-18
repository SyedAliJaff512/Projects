<?php
session_start();
require 'db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if (!$email) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "An account with this email already exists.";
        } else {
            // Register new user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'user')");
            $stmt->bind_param("ss", $email, $hash);
            
            if ($stmt->execute()) {
                // Auto-login the new user
                $user_id = $conn->insert_id;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'user';
                
                $success = true;
                header("Refresh:2; url=dashboard_user.php");
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <h1>User Registration</h1>
        <pre><b>Project Developers:</b>Syed Muhammad ali
                Sanaila Sajjad
                Rameesha Ilyas</pre>
    </header>
    <div class="container">
        <?php if ($success): ?>
            <div class="success">Registration successful! Redirecting to your dashboard...</div>
        <?php elseif ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST" autocomplete="off">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required autofocus>

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required minlength="6">

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required minlength="6">

            <button type="submit">Register</button>
        </form>
        <p style="text-align:center; margin-top:1rem;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
        <?php endif; ?>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System
    </footer>
</body>
</html>
