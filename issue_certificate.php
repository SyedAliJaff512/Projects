<?php
require 'session_check.php';
require 'db.php';

// Only allow teachers
if ($_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$teacher_id = null;
$message = '';

// Get teacher_id from user_id
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($teacher_id);
$stmt->fetch();
$stmt->close();

if (!$teacher_id) {
    die("Teacher record not found. Please contact support.");
}

// Fetch courses assigned to this teacher
$courses = [];
$stmt = $conn->prepare("
    SELECT c.course_id, c.title 
    FROM courses c
    JOIN teacher_courses tc ON c.course_id = tc.course_id
    WHERE tc.teacher_id = ?
");
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

$students = [];
$selected_course = null;
$selected_student = null;

if (isset($_GET['course_id'])) {
    $selected_course = (int)$_GET['course_id'];

    // Verify course belongs to this teacher
    $valid_course = false;
    foreach ($courses as $course) {
        if ($course['course_id'] === $selected_course) {
            $valid_course = true;
            break;
        }
    }

    if ($valid_course) {
        // Fetch students enrolled in the selected course
        $stmt = $conn->prepare("
            SELECT s.student_id, s.fullname 
            FROM enrollments e
            JOIN students s ON e.user_id = s.user_id
            WHERE e.course_id = ?
        ");
        $stmt->bind_param("i", $selected_course);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    } else {
        $message = '<div class="error">You are not authorized to issue certificates for this course.</div>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['student_id'], $_POST['certificate_title'], $_POST['issue_date'])) {
    $course_id = (int)$_POST['course_id'];
    $student_id = (int)$_POST['student_id'];
    $certificate_title = trim($_POST['certificate_title']);
    $issue_date = $_POST['issue_date'];

    // Validate inputs
    if ($certificate_title === '') {
        $message = '<div class="error">Certificate title is required.</div>';
    } elseif (empty($issue_date)) {
        $message = '<div class="error">Issue date is required.</div>';
    } else {
        // Check if teacher is authorized for this course
        $authorized = false;
        foreach ($courses as $course) {
            if ($course['course_id'] === $course_id) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            $message = '<div class="error">You are not authorized to issue certificates for this course.</div>';
        } else {
            // Insert certificate record (removing 'title' column since it doesn't exist)
            $stmt = $conn->prepare("
                INSERT INTO certificates 
                (student_id, course_id, issued_at) 
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("iis", $student_id, $course_id, $issued_at);
            if ($stmt->execute()) {
                $message = '<div class="success">Certificate issued successfully!</div>';
            } else {
                $message = '<div class="error">Database error: ' . htmlspecialchars($stmt->error) . '</div>';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Issue Certificate | Teacher Panel</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        nav {
            background:  #004080;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
        }
        nav ul li {
            margin: 0 1rem;
        }
        nav ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            transition: background 0.2s, color 0.2s;
        }
        nav ul li a:hover, nav ul li a:focus {
            background: #0073e6;
            color: #fff;
            outline: none;
        }
        nav ul li a.logout-btn {
            background: #ffeaea;
            color: #c00 !important;
        }
        .container {
            max-width: 700px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 10px;
            padding: 2.5rem 2rem;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
        }
        h1 {
            color: #004080;
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
        }
        form label {
            font-weight: 600;
            color: #004080;
            display: block;
            margin-bottom: 0.5rem;
        }
        form select, form input[type="text"], form input[type="date"] {
            width: 100%;
            padding: 0.7rem;
            border-radius: 6px;
            border: 1px solid #cfd8dc;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            background: #fff;
        }
        button {
            background: #0073e6;
            color: #fff;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover, button:focus {
            background: #004080;
            outline: none;
        }
        .success {
            background: #e6ffea;
            color: #008a3a;
            border: 1px solid #b3ffd3;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .error {
            background: #ffe5e5;
            color: #c00;
            border: 1px solid #ffb3b3;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="dashboard_teacher.php">Dashboard</a></li>
            <li><a href="upload_quiz_assignment.php">Upload Quiz/Assignment</a></li>
            <li><a href="mark_attendance.php">Mark Attendance</a></li>
            <li><a href="issue_certificate.php" class="active">Issue Certificate</a></li>
            <li><a href="support_teacher.php">Support</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
    <div class="container">
        <h1>Issue Certificate</h1>

        <?= $message ?>

        <form method="GET" action="issue_certificate.php">
            <label for="course_id">Select Course:</label>
            <select name="course_id" id="course_id" required onchange="this.form.submit()">
                <option value="">-- Select Course --</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>" <?= ($selected_course == $course['course_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if ($selected_course && !empty($students)): ?>
            <form method="POST" action="issue_certificate.php">
                <input type="hidden" name="course_id" value="<?= $selected_course ?>">
                <label for="student_id">Select Student:</label>
                <select name="student_id" id="student_id" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['student_id'] ?>"><?= htmlspecialchars($student['fullname']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="certificate_title">Certificate Title:</label>
                <input type="text" name="certificate_title" id="certificate_title" required maxlength="255" placeholder="e.g. Completion of Course XYZ">

                <label for="issue_date">Issue Date:</label>
                <input type="date" name="issue_date" id="issue_date" required value="<?= date('Y-m-d') ?>">

                <button type="submit">Issue Certificate</button>
            </form>
        <?php elseif ($selected_course): ?>
            <p>No students enrolled in this course.</p>
        <?php endif; ?>
    </div>
</body>
</html>
