<?php
require 'session_check.php';
require 'db.php';

// Fetch support info (fallback if not found)
$support = $conn->query("SELECT * FROM support_info LIMIT 1")->fetch_assoc();
$support_info_exists = $support ? true : false;

if (!$support) {
    $support = [
        'contact_email' => 'info@ecourses.pk',
        'contact_phone' => '+92-300-0000000',
        'address' => '123 Main Road, Lahore, Pakistan',
        'map_embed' => '<iframe class="support-map" src="https://maps.google.com/maps?q=lahore&t=&z=13&ie=UTF8&iwloc=&output=embed" allowfullscreen="" loading="lazy"></iframe>',
        'user_message' => ''
    ];
}

$message_status = '';
// Handle support message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['support_message'])) {
    $user_message = trim($_POST['support_message']);
    if ($user_message === '') {
        $message_status = '<div class="error">Please enter your support message.</div>';
    } else {
        if ($support_info_exists) {
            // Update the user_message column in support_info table
            $stmt = $conn->prepare("UPDATE support_info SET user_message = ? LIMIT 1");
            $stmt->bind_param("s", $user_message);
        } else {
            // Insert a new row if support_info table is empty
            $stmt = $conn->prepare("INSERT INTO support_info (contact_email, contact_phone, address, map_embed, user_message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $support['contact_email'], $support['contact_phone'], $support['address'], $support['map_embed'], $user_message);
        }
        if ($stmt->execute()) {
            $message_status = '<div class="success">Your message has been sent. Our support team will contact you soon.</div>';
            $support['user_message'] = $user_message;
        } else {
            $message_status = '<div class="error">Failed to send your message. Please try again later.<br>Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .support-container {
            max-width: 600px;
            margin: 2.5rem auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 2.5rem 2rem;
        }
        .support-header {
            color:rgb(255, 255, 255);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .support-info {
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .support-info strong {
            color: #0073e6;
        }
        .support-map {
            width: 100%;
            height: 300px;
            border: none;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .support-form {
            margin-top: 2rem;
        }
        textarea {
            width: 100%;
            min-height: 80px;
            padding: 0.8rem;
            border-radius: 6px;
            border: 1px solid #cfd8dc;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .support-form button {
            background: #0073e6;
            color: #fff;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .support-form button:hover {
            background: #004080;
        }
        .user-message-view {
            background: #f8fafc;
            border: 1px solid #e3e7ed;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #333;
        }
    </style>
</head>
<body>
    <header>
        <h1 class="support-header">Support & Contact Information</h1>
    </header>
       <nav class="student-nav" aria-label="Student Navigation">
       <a href="dashboard_student.php">Dashboard</a>
        <a href="courses_student.php">My Courses</a>
        <a href="assignments_students.php">Assignments</a>
        <a href="support_student.php">Support</a>
        <a href="logout.php" style="background:#ffeaea; color:#c00;">Logout</a>
    </nav>
    <div class="support-container">
        <?= $message_status ?>
        <div class="support-info">
            <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($support['contact_email']) ?>">
                <?= htmlspecialchars($support['contact_email']) ?></a></p>
            <p><strong>Phone:</strong> <a href="tel:<?= htmlspecialchars($support['contact_phone']) ?>">
                <?= htmlspecialchars($support['contact_phone']) ?></a></p>
            <p><strong>Address:</strong> <?= htmlspecialchars($support['address']) ?></p>
        </div>
        <?php if (!empty($support['map_embed'])): ?>
            <div>
                <?= $support['map_embed'] ?>
            </div>
        <?php endif; ?>

        <form class="support-form" method="POST" autocomplete="off">
            <label for="support_message"><strong>Send us a message for help or support:</strong></label>
            <textarea name="support_message" id="support_message" required placeholder="Type your message here..."></textarea>
            <button type="submit">Send Message</button>
        </form>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
