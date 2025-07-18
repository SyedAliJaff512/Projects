<?php
require 'session_check.php';
require 'db.php';

if ($_SESSION['role'] !== 'user') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$message = '';
$enrollment_id = isset($_GET['enrollment_id']) ? (int)$_GET['enrollment_id'] : 0;

if ($enrollment_id > 0) {
    // Fetch enrollment details with course information
    $stmt = $conn->prepare("
        SELECT e.*, c.title, c.price, c.description 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.enrollment_id = ? AND e.user_id = ?
    ");
    $stmt->bind_param("ii", $enrollment_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollment = $result->fetch_assoc();

    if (!$enrollment || $enrollment['payment_status'] !== 'pending') {
        $enrollment = null;
        $message = "<div class='error'>Invalid or already paid enrollment access.</div>";
    }
} else {
    $enrollment = null;
}

// Payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay_enrollment_id'])) {
    $enrollment_id = (int)$_POST['pay_enrollment_id'];
    $card_number = preg_replace('/\D/', '', $_POST['card_number']);
    $expiry = $_POST['expiry_month'];
    $cvv = $_POST['cvv'];

    // Fetch enrollment for validation
    $stmt = $conn->prepare("
        SELECT e.*, c.title, c.price, c.description 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.enrollment_id = ? AND e.user_id = ?
    ");
    $stmt->bind_param("ii", $enrollment_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollment = $result->fetch_assoc();

    if (!$enrollment || $enrollment['payment_status'] !== 'pending') {
        $message = "<div class='error'>Invalid or already paid enrollment access.</div>";
    } elseif (!preg_match('/^\d{11}$/', $card_number)) {
        $message = "<div class='error'>Card number must be exactly 11 digits.</div>";
    } elseif (!preg_match('/^\d{3}$/', $cvv)) {
        $message = "<div class='error'>Invalid CVV</div>";
    } else {
        try {
            $conn->begin_transaction();
            $last4 = substr($card_number, -4);

            // Update enrollment
            $update = $conn->prepare("UPDATE enrollments SET payment_status = 'paid' WHERE enrollment_id = ?");
            $update->bind_param("i", $enrollment_id);
            $update->execute();

            // Insert payment record
            $insert = $conn->prepare("INSERT INTO payments (enrollment_id, amount, payment_date, card_last4) VALUES (?, ?, NOW(), ?)");
            $insert->bind_param("ids", $enrollment_id, $enrollment['price'], $last4);
            $insert->execute();

            $conn->commit();
            $message = "<div class='success'>Payment successful! You are now enrolled in {$enrollment['title']}.</div>";
            $enrollment = null; // Hide form after payment
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='error'>Payment failed: " . $e->getMessage() . "</div>";
        }
    }
}

// If no enrollment selected, show pending enrollments
$pending_enrollments = [];
if (!$enrollment) {
    $sql = "
        SELECT e.enrollment_id, c.title, c.price, c.description
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.user_id = {$_SESSION['user_id']} AND e.payment_status = 'pending' AND e.mode = 'online'
    ";
    $res = $conn->query($sql);
    while ($row = $res->fetch_assoc()) {
        $pending_enrollments[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .course-info {
            background: #f8fafc;
            border: 1px solid #e3e7ed;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
        }
        .course-info h2 { color: #004080; margin-bottom: 0.5rem; }
        .course-info .price { color: #0073e6; font-weight: bold; }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        label {
            font-weight: 600;
            color: #004080;
        }
        input {
            padding: 0.8rem;
            border: 1px solid #cfd8dc;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            background: #0073e6;
            color: #fff;
            border: none;
            padding: 1rem;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover {
            background: #004080;
        }
        .success {
            background: #e6ffea;
            color: #008a3a;
            border: 1px solid #b3ffd3;
            padding: 0.7rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error {
            background: #ffe5e5;
            color: #c00;
            border: 1px solid #ffb3b3;
            padding: 0.7rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .pending-list {
            margin-bottom: 2rem;
        }
        .pending-course {
            background: #f9f9f9;
            border: 1px solid #e3e7ed;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1>Course Payment</h1>
    </header>
    <nav>
        <a href="dashboard_user.php">Dashboard</a>
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
        <?= $message ?>
        <?php if ($enrollment): ?>
            <div class="course-info">
                <h2><?= htmlspecialchars($enrollment['title']) ?></h2>
                <div class="desc"><?= nl2br(htmlspecialchars($enrollment['description'])) ?></div>
                <div class="price">Amount Due: Rs. <?= number_format($enrollment['price'], 2) ?></div>
            </div>
            <form method="POST">
                <input type="hidden" name="pay_enrollment_id" value="<?= $enrollment_id ?>">
                <label for="card_number">Card Number (11 digits)</label>
                <input 
                    type="text" 
                    id="card_number" 
                    name="card_number" 
                    pattern="\d{11}" 
                    maxlength="11" 
                    minlength="11" 
                    required 
                    placeholder="12345678901">
                <label for="expiry_month">Expiry Date</label>
                <input type="month" id="expiry_month" name="expiry_month" required>
                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" pattern="\d{3}" required placeholder="123">
                <button type="submit">Complete Payment</button>
            </form>
        <?php elseif (!empty($pending_enrollments)): ?>
            <div class="pending-list">
                <h2>Pending Online Course Payments</h2>
                <?php foreach ($pending_enrollments as $pending): ?>
                    <div class="pending-course">
                        <strong><?= htmlspecialchars($pending['title']) ?></strong><br>
                        <span>Rs. <?= number_format($pending['price'], 2) ?></span><br>
                        <a href="payment_user.php?enrollment_id=<?= $pending['enrollment_id'] ?>" class="btn">Pay Now</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="success">No pending online course payments. Please enroll in a course to proceed with payment.</div>
        <?php endif; ?>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
