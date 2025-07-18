<?php
require 'session_check.php';
require 'db.php';

// Only allow students to access this dashboard
if ($_SESSION['role'] !== 'student') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch enrolled courses
$courses = [];
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

// Fetch assignments/quizzes with deadline and teacher info
$assignments = [];
if (!empty($courses)) {
    $course_ids = array_column($courses, 'course_id');
    $placeholders = implode(',', array_fill(0, count($course_ids), '?'));
    $types = str_repeat('i', count($course_ids));
    $stmt = $conn->prepare("
        SELECT a.assignment_id, a.title, a.file_path, a.uploaded_at, a.deadline, c.title AS course_title
        FROM assignments a
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.course_id IN ($placeholders)
    ");
    $stmt->bind_param($types, ...$course_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}

// Handle assignment solution submission (only before deadline)
$submission_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'], $_FILES['solution_file'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    // Fetch assignment deadline
    $stmt = $conn->prepare("SELECT deadline FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $stmt->bind_result($deadline);
    $stmt->fetch();
    $stmt->close();

    $now = date('Y-m-d H:i:s');
    if ($now > $deadline) {
        $submission_message = '<div class="error">Deadline has passed. You cannot submit a solution for this assignment.</div>';
    } else {
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
            if ($stmt->execute()) {
                $submission_message = '<div class="success">Solution uploaded successfully!</div>';
            } else {
                $submission_message = '<div class="error">Database error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        } else {
            $submission_message = '<div class="error">Failed to upload file.</div>';
        }
    }
}

// Attendance records (per course)
$attendance = [];
$stmt = $conn->prepare("
    SELECT c.course_id, c.title, COUNT(a.attendance_id) AS total_classes,
           SUM(a.status='present') AS attended
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.student_id = ?
    GROUP BY a.course_id
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $attendance[] = $row;
}
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .dashboard-header { text-align: center; margin-top: 2rem; color:rgb(255, 255, 255); font-weight: bold; }
        .container { max-width: 900px; margin: 3rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 2.5rem 2rem; }
        .section { margin-bottom: 2.5rem; }
        .section h2 { color: #0073e6; margin-bottom: 1rem; }
        .assignment-card, .lecture-card { padding: 1rem; margin: 1rem 0; border: 1px solid #e3e7ed; border-radius: 6px; background: #f8fafc; }
        .assignment-form { display: grid; gap: 1rem; margin-top: 1rem; }
        .success { background: #e6ffea; color: #008a3a; border: 1px solid #b3ffd3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #ffe5e5; color: #c00; border: 1px solid #ffb3b3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .attendance-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .attendance-table th, .attendance-table td { padding: 0.8rem; border: 1px solid #e3e7ed; text-align: left; }
        .btn { background: #0073e6; color: #fff; padding: 0.7rem 1.2rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: background 0.2s; display: inline-block; }
        .btn:hover { background: #004080; }
        nav { display: flex; justify-content: center; flex-wrap: wrap; gap: 1.2rem; background: #f1f6fb; padding: 1rem 0; border-bottom: 1px solid #dde5ef; margin-bottom: 2rem; }
        nav a { color: #004080; text-decoration: none; font-weight: 500; padding: 0.5rem 1.2rem; border-radius: 20px; transition: background 0.2s, color 0.2s; }
        nav a:hover, nav a:focus { background: #0073e6; color: #fff; outline: none; }
        nav a[style*="color:#c00"] { color: #c00 !important; background: #ffeaea; }
        @media (max-width: 700px) { .container { padding: 1rem; } nav { flex-direction: column; gap: 0.5rem; } }
    </style>
</head>
<body>
    <header>
        <h1 class="dashboard-header">Welcome, Student!</h1>
    </header>
   <nav>
    <a href="dashboard_student.php">Dashboard</a>
    <a href="courses_student.php">My Courses</a>
    <a href="assignments_students.php">Assignment</a>
    <a href="support_student.php">Support</a>
    <a href="logout.php" style="color:#c00;">Logout</a>
</nav>

    <div class="container">

        <!-- Enrolled Courses Section -->
        <div class="section">
            <h2>Your Enrolled Courses</h2>
            <?php if (empty($courses)): ?>
                <p>No enrolled courses found.</p>
            <?php else: ?>
                <ul>
                <?php foreach ($courses as $course): ?>
                    <li>
                        <strong><?= htmlspecialchars($course['title']) ?></strong>
                        <br><small><?= nl2br(htmlspecialchars($course['description'])) ?></small>
                        <br><small>Enrolled on: <?= date('M d, Y', strtotime($course['enrolled_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Assignments & Quizzes Submission Button Section -->
<div class="section">
    <h2>Assignments & Quizzes</h2>
    <a href="assignments_students.php" class="btn">Go to Assignment Submission</a>
</div>


        <!-- Attendance Section -->
        <div class="section">
            <h2>View Attendance</h2>
            <?php if (empty($attendance)): ?>
                <p>No attendance records found.</p>
            <?php else: ?>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Classes Attended</th>
                            <th>Total Classes</th>
                            <th>Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['title']) ?></td>
                                <td><?= (int)$record['attended'] ?></td>
                                <td><?= (int)$record['total_classes'] ?></td>
                                <td>
                                    <?= $record['total_classes'] > 0 
                                        ? round(($record['attended'] / $record['total_classes']) * 100, 2) . '%' 
                                        : 'N/A' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
