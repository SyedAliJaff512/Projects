<?php
require 'session_check.php';
require 'db.php';

// Handle course registration and mode selection
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['mode'])) {
    $course_id = (int)$_POST['course_id'];
    $mode = $_POST['mode'] === 'online' ? 'online' : 'on-site';
    $user_id = $_SESSION['user_id'];

    // Check if already enrolled
    $check = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check->bind_param("ii", $user_id, $course_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "<div class='error'>You are already enrolled in this course.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, mode, payment_status) VALUES (?, ?, ?, ?)");
        $payment_status = ($mode == 'online') ? 'pending' : 'paid';
        $stmt->bind_param("iiss", $user_id, $course_id, $mode, $payment_status);

        if ($stmt->execute()) {
            $enrollment_id = $stmt->insert_id;
            if ($mode == 'online') {
                header("Location: payment_user.php?enrollment_id=$enrollment_id");
                exit();
            } else {
                $message = "<div class='success'>Enrolled in course (On-Site). Payment will be handled on campus.</div>";
            }
        } else {
            $message = "<div class='error'>Enrollment failed. Please try again.</div>";
        }
        $stmt->close();
    }
    $check->close();
}

// Fetch all courses
$courses = [];
$res = $conn->query("SELECT * FROM courses");
while ($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courses | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .course-list { margin: 2rem auto; max-width: 900px; }
        .course-card {
            background: #f8fafc;
            border: 1px solid #e3e7ed;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 6px rgba(0,0,0,0.03);
        }
        .course-card h3 { color: #004080; margin-bottom: 0.5rem; }
        .course-card .price { color: #0073e6; font-weight: bold; }
        .course-card form { margin-top: 1rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; }
        .course-card select, .course-card button { padding: 0.5rem 1rem; border-radius: 4px; border: 1px solid #cfd8dc; }
        .course-card button { background: #0073e6; color: #fff; border: none; font-weight: 600; cursor: pointer; }
        .course-card button:hover { background: #004080; }
    </style>
</head>
<body>
    <header>
        <h1>Available Courses</h1>
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
        <?= $message ?>
        <div class="course-list">
            <?php if (empty($courses)): ?>
                <div class='error'>No courses available at the moment.</div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="desc"><?= nl2br(htmlspecialchars($course['description'])) ?></div>
                        <div class="price">Price: Rs. <?= number_format($course['price'], 2) ?></div>
                        <form method="POST">
                            <input type="hidden" name="course_id" value="<?= $course['course_id'] ?>">
                            <label for="mode_<?= $course['course_id'] ?>">Choose Mode:</label>
                            <select name="mode" id="mode_<?= $course['course_id'] ?>" required>
                                <?php if ($course['mode'] == 'online' || $course['mode'] == 'both'): ?>
                                    <option value="online">Online</option>
                                <?php endif; ?>
                                <?php if ($course['mode'] == 'on-site' || $course['mode'] == 'both'): ?>
                                    <option value="on-site">On-Site</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit">Register in Course</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
