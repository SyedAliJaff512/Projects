<?php
require 'session_check.php';
require 'db.php';

if ($_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Get teacher_id from teachers table using user_id
$stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($teacher_id);
$stmt->fetch();
$stmt->close();

if (!$teacher_id) {
    die("Teacher not found");
}

$message = '';

// Process attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'], $_POST['attendance'])) {
    $date = date('Y-m-d');
    $course_id = (int)$_POST['course_id'];
    $conn->begin_transaction();
    try {
        foreach ($_POST['attendance'] as $student_id => $status) {
            $student_id = (int)$student_id;
            $status = in_array($status, ['present', 'absent']) ? $status : 'absent';
            $stmt = $conn->prepare("INSERT INTO attendance 
                (student_id, course_id, date, status) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = VALUES(status)");
            $stmt->bind_param("iiss", $student_id, $course_id, $date, $status);
            if (!$stmt->execute()) {
                throw new Exception("Error recording attendance for student $student_id");
            }
            $stmt->close();
        }
        $conn->commit();
        $message = '<div class="success">Attendance recorded successfully!</div>';
    } catch (Exception $e) {
        $conn->rollback();
        $message = '<div class="error">Error: ' . $e->getMessage() . '</div>';
    }
}

// Fetch courses assigned to this teacher from teacher_courses
$courses = [];
$stmt = $conn->prepare("SELECT c.course_id, c.title FROM courses c 
    JOIN teacher_courses tc ON c.course_id = tc.course_id 
    WHERE tc.teacher_id = ?");
$stmt->bind_param("s", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Fetch students for selected course
$students = [];
$selected_course = null;
$course_title = '';

if (isset($_GET['course_id'])) {
    $course_id = (int)$_GET['course_id'];
    $selected_course = $course_id;
    
    // Verify the course belongs to this teacher
    $valid_course = false;
    foreach ($courses as $course) {
        if ($course['course_id'] == $course_id) {
            $course_title = $course['title'];
            $valid_course = true;
            break;
        }
    }
    
    if (!$valid_course) {
        $message = '<div class="error">You are not assigned to teach this course</div>';
    } else {
        $stmt = $conn->prepare("SELECT s.student_id, s.fullname 
            FROM enrollments e
            JOIN students s ON e.user_id = s.user_id
            WHERE e.course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance | Teacher Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
body {
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
background-color: #f5f7fa;
margin: 0;
padding: 0;
color: #333;
}
.container {
max-width: 800px;
margin: 30px auto;
padding: 25px;
background: white;
border-radius: 10px;
box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
}
header {
text-align: center;
margin-bottom: 30px;
padding-bottom: 20px;
border-bottom: 1px solid #eaeaea;
}
h1 {
color:rgb(255, 255, 255);
margin-bottom: 10px;
}
.card {
background: #f8f9fa;
padding: 25px;
border-radius: 8px;
margin-bottom: 30px;
border: 1px solid #e9ecef;
}
.form-group {
margin-bottom: 20px;
}
label {
display: block;
margin-bottom: 8px;
font-weight: 600;
color:rgb(0, 0, 0);
}
select {
width: 100%;
padding: 12px;
border: 1px solid #ced4da;
border-radius: 6px;
font-size: 16px;
background: white;
}
.btn {
background: #3498db;
color: white;
border: none;
padding: 12px 25px;
border-radius: 6px;
cursor: pointer;
font-size: 16px;
font-weight: 600;
transition: background 0.3s;
display: inline-block;
text-decoration: none;
}
.btn:hover {
background: #2980b9;
}
.btn-primary {
background: #2ecc71;
}
.btn-primary:hover {
background: #27ae60;
}
.attendance-table {
width: 100%;
border-collapse: collapse;
margin-top: 20px;
}
.attendance-table th,
.attendance-table td {
padding: 15px;
text-align: left;
border-bottom: 1px solid #e9ecef;
}
.attendance-table th {
background-color: #f8f9fa;
font-weight: 600;
}
.attendance-table tr:hover {
background-color: #f8f9fa;
}
.attendance-options {
display: flex;
gap: 15px;
}
.attendance-options label {
display: flex;
align-items: center;
gap: 8px;
cursor: pointer;
font-weight: normal;
margin-bottom: 0;
}
.success {
background: #d4edda;
color: #155724;
padding: 15px;
border-radius: 6px;
margin-bottom: 20px;
border: 1px solid #c3e6cb;
}
.error {
background: #f8d7da;
color: #721c24;
padding: 15px;
border-radius: 6px;
margin-bottom: 20px;
border: 1px solid #f5c6cb;
}
nav {
background: #004080;
padding: 15px 0;
margin-bottom: 30px;
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
margin: 5px 15px;
}
nav ul li a {
color: white;
text-decoration: none;
font-weight: 500;
font-size: 16px;
padding: 8px 15px;
border-radius: 4px;
transition: background 0.3s;
}
nav ul li a:hover {
background: #3498db;
}
.logout-btn {
background: #e74c3c;
}
.logout-btn:hover {
background: #c0392b;
}
.course-title {
text-align: center;
margin: 20px 0;
color: #2c3e50;
font-size: 1.4rem;
}
</style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="dashboard_teacher.php">Dashboard</a></li>
            <li><a href="upload_quiz_assignment.php">Upload Quiz/Assignment</a></li>
            <li><a href="mark_attendance.php">Mark Attendance</a></li>
            <li><a href="issue_certificate.php">Issue Certificate</a></li>
            <li><a href="support_teacher.php">Support</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <header>
            <h1>Mark Student Attendance</h1>
            <p>Select a course to mark attendance for today (<?= date('M d, Y') ?>)</p>
        </header>

        <?= $message ?>

        <div class="card">
            <form method="GET" action="mark_attendance.php">
                <div class="form-group">
                    <label for="course_id">Select Course:</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" 
                                <?= $selected_course == $course['course_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Load Students</button>
            </form>
        </div>

        <?php if (!empty($students)): ?>
            <div class="course-title">Marking attendance for: <strong><?= htmlspecialchars($course_title) ?></strong></div>
            
            <form method="POST">
                <input type="hidden" name="course_id" value="<?= $selected_course ?>">
                
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['student_id']) ?></td>
                                <td><?= htmlspecialchars($student['fullname']) ?></td>
                                <td>
                                    <div class="attendance-options">
                                        <label>
                                            <input type="radio" 
                                                   name="attendance[<?= $student['student_id'] ?>]" 
                                                   value="present" 
                                                   checked> Present
                                        </label>
                                        <label>
                                            <input type="radio" 
                                                   name="attendance[<?= $student['student_id'] ?>]" 
                                                   value="absent"> Absent
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" class="btn btn-primary">Save Attendance</button>
            </form>
        <?php elseif ($selected_course && empty($students)): ?>
            <div class="card">
                <p>No students enrolled in this course.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
