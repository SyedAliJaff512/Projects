<?php
require 'session_check.php';
require 'db.php';

// Only allow teachers to access this page
if ($_SESSION['role'] !== 'teacher') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$teacher_id = null;

// Get teacher_id for this user
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($teacher_id);
$stmt->fetch();
$stmt->close();

$error = $success = '';

// Fetch courses assigned to this teacher (via teacher_courses table)
$courses = [];
$course_stmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN teacher_courses tc ON c.course_id = tc.course_id
    WHERE tc.teacher_id = ?
");
$course_stmt->bind_param("s", $teacher_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();
while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row;
}
$course_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['title'])) {
    $course_id = $_POST['course_id'];
    $title = trim($_POST['title']);

    // Handle file upload
    if (!isset($_FILES['assignment_file']) || $_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed. Please select a file.";
    } elseif ($title === '') {
        $error = "Assignment title is required.";
    } else {
        $upload_dir = 'uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['assignment_file']['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_path)) {
            // Save assignment info to database
            $stmt = $conn->prepare("INSERT INTO assignments (course_id, teacher_id, title, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $course_id, $teacher_id, $title, $target_path);
            if ($stmt->execute()) {
                $success = "Assignment uploaded successfully!";
            } else {
                $error = "Database error: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $error = "Failed to move uploaded file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Quiz/Assignment | Teacher Panel</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { background: #f5f7fa; font-family: Segoe UI, Arial, sans-serif; margin: 0; }
        .container { max-width: 600px; margin: 2.5rem auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 2.5rem 2rem; }
        header { text-align: center; margin-bottom: 2rem; }
        h1 { color:rgb(255, 255, 255); font-weight: bold; letter-spacing: 1px; }
        nav { display: flex; flex-wrap: wrap; gap: 1.2rem; justify-content: center; margin-bottom: 2rem; }
        nav a { background: #0073e6; color: #fff; padding: 0.8rem 1.5rem; border-radius: 6px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        nav a:hover, nav a:focus { background: #004080; outline: none; }
        nav a[style*="color:#c00"] { background: #ffeaea; color: #c00 !important; }
        .form-section { margin-bottom: 2rem; }
        label { font-weight: 600; color: #004080; }
        input, select { width: 100%; padding: 0.7rem; border-radius: 6px; border: 1px solid #cfd8dc; font-size: 1rem; margin-bottom: 1rem; background: #fff; }
        button { background: #0073e6; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 0.5rem; }
        button:hover { background: #004080; }
        .success { background: #e6ffea; color: #008a3a; border: 1px solid #b3ffd3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #ffe5e5; color: #c00; border: 1px solid #ffb3b3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        @media (max-width: 700px) {
            .container, header { padding: 1rem; }
            nav { flex-direction: column; gap: 0.7rem; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Upload Quiz/Assignment</h1>
    </header>
    <nav>
        <a href="dashboard_teacher.php">Dashboard</a>
        <a href="upload_quiz_assignment.php">Upload Quiz/Assignment</a>
        <a href="mark_attendance.php">Mark Attendance</a>
        <a href="issue_certificate.php">Issue Certificate</a>
        <a href="support_teacher.php">Support</a>
        <a href="logout.php" style="color:#c00;">Logout</a>
    </nav>
    <div class="container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="form-section" autocomplete="off">
            <label for="course_id">Select Course:</label>
            <select name="course_id" id="course_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="title">Assignment/Quiz Title:</label>
            <input type="text" name="title" id="title" required maxlength="100">

            <label for="assignment_file">Upload File (PDF, DOC, DOCX, TXT, ZIP):</label>
            <input type="file" name="assignment_file" id="assignment_file" accept=".pdf,.doc,.docx,.txt,.zip" required>

            <button type="submit">Upload</button>
        </form>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System. All rights reserved.
    </footer>
</body>
</html>
