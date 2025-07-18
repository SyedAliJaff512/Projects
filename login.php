<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            header("Location: dashboard_{$user['role']}.php");
            exit();
        }
    }
    $_SESSION['error'] = "Invalid credentials";
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | E-Courses System</title>
    <link rel="stylesheet" href="style.css"> <!-- CSS linked here -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <header>
        <h1>E-Courses Login</h1>
        <pre><b>Project Developers:</b>Syed Muhammad ali
                Sanaila Sajjad
                Rameesha Ilyas</pre>
    </header>
    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required autofocus>
            
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
            
            <button type="submit">Login</button>
        </form>
        <p style="text-align:center; margin-top:1rem;">
            Don't have an account? <a href="register_user.php">Register here</a>
        </p>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System
    </footer>
</body>
</html>
