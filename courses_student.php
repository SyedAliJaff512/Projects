<?php
require 'session_check.php';
require 'db.php';

// Only allow students
if ($_SESSION['role'] !== 'student') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$courses = [];
$assignments = [];
$attendance = [];

// Fetch enrolled courses
$stmt = $conn->prepare("
    SELECT c.course_id, c.title, c.description, e.enrolled_at 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.course_id
    WHERE e.user_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Fetch assignments for enrolled courses
if (!empty($courses)) {
    $course_ids = array_column($courses, 'course_id');
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.file_path, a.uploaded_at, c.title AS course_title
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.course_id IN ($placeholders)
    ");
    $stmt->bind_param(str_repeat('i', count($course_ids)), ...$course_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}

// Fetch attendance
$stmt = $conn->prepare("
    SELECT c.title AS course_name, a.date, a.status 
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}
$stmt->close();

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['solution_file'])) {
    $assignment_id = $_POST['assignment_id'];
    $upload_dir = 'uploads/solutions/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = time() . '_' . basename($_FILES['solution_file']['name']);
    $target_path = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['solution_file']['tmp_name'], $target_path)) {
        $stmt = $conn->prepare("
            INSERT INTO assignment_submissions 
            (assignment_id, student_id, file_path, submitted_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $assignment_id, $student_id, $target_path);
        $stmt->execute();
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses | Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .course-section, .assignment-section, .attendance-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .course-card {
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid #e3e7ed;
            border-radius: 6px;
        }
        .assignment-form {
            display: grid;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .attendance-table th, .attendance-table td {
            padding: 0.8rem;
            border: 1px solid #e3e7ed;
            text-align: left;
        }
    </style>
</head>
<body>
    <header>
        <h1>My Enrolled Courses</h1>
    </header>
    <nav>
        <a href="dashboard_student.php">Dashboard</a>
        <a href="courses_student.php">My Courses</a>
        <a href="assignments_students.php">Assignments</a>
        <a href="support_student.php">Support</a>
        <a href="logout.php" style="color:#c00;">Logout</a>
    </nav>

    <div class="container">
        <!-- Enrolled Courses Section -->
        <section class="course-section">
            <h2>Your Courses</h2>
            <?php if (empty($courses)): ?>
                <p>No enrolled courses found.</p>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <h3><?= htmlspecialchars($course['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($course['description'])) ?></p>
                        <small>Enrolled on: <?= date('M d, Y', strtotime($course['enrolled_at'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Assignments Section -->
        <section class="assignment-section">
            <h2>Assignments & Quizzes</h2>
            <?php if (empty($assignments)): ?>
                <p>No active assignments found.</p>
            <?php else: ?>
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <h4><?= htmlspecialchars($assignment['course_title']) ?>: <?= htmlspecialchars($assignment['title']) ?></h4>
                        <p>Posted on: <?= date('M d, Y', strtotime($assignment['uploaded_at'])) ?></p>
                        <a href="<?= htmlspecialchars($assignment['file_path']) ?>" target="_blank">Download Assignment</a>
                        
                        <form class="assignment-form" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="assignment_id" value="<?= $assignment['assignment_id'] ?>">
                            <input type="file" name="solution_file" required accept=".pdf,.doc,.docx,.txt">
                            <button type="submit">Upload Solution</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Attendance Section -->
        <section class="attendance-section">
            <h2>Attendance Record</h2>
            <?php if (empty($attendance)): ?>
                <p>No attendance records found.</p>
            <?php else: ?>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['course_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                <td><?= ucfirst($record['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System
    </footer>
</body>
</html>
