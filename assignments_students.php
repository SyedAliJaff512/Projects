<?php
require 'session_check.php';
require 'db.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Get the actual student_id for this user
$student_id = null;
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    die("Student record not found. Please contact support.");
}


// Fetch enrolled courses for dropdown
$courses = [];
$stmt = $conn->prepare("
    SELECT c.course_id, c.title 
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

// Fetch assignments for selected course
$assignments = [];
if (isset($_POST['course_id']) || isset($_GET['course_id'])) {
    $course_id = isset($_POST['course_id']) ? (int)$_POST['course_id'] : (int)$_GET['course_id'];
    $stmt = $conn->prepare("
        SELECT assignment_id, title, uploaded_at 
        FROM assignments 
        WHERE course_id = ?
    ");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
    $stmt->close();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['assignment']) && isset($_POST['assignment_id'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $file = $_FILES['assignment'];
    
    if (!$assignment_id) {
        $message = '<div class="error">Please select an assignment</div>';
    } else {
        $allowed = ['pdf', 'doc', 'docx', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            $message = '<div class="error">Invalid file type. Allowed: PDF, DOC, DOCX, ZIP</div>';
        } elseif ($file['size'] > 5242880) { // 5MB
            $message = '<div class="error">File too large (max 5MB)</div>';
        } else {
            $filename = uniqid() . '.' . $ext;
            $path = "uploads/solutions/" . $filename;
            
            // Create directory if it doesn't exist
            if (!is_dir("uploads/solutions/")) {
                mkdir("uploads/solutions/", 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $path)) {
                // Insert into assignment_submissions table
                $stmt = $conn->prepare("INSERT INTO assignment_submissions 
                    (assignment_id, student_id, file_path, submitted_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $assignment_id, $student_id, $path);
                if ($stmt->execute()) {
                    $message = '<div class="success">Assignment submitted successfully!</div>';
                } else {
                    $message = '<div class="error">Database error: ' . htmlspecialchars($stmt->error) . '</div>';
                }
                $stmt->close();
            } else {
                $message = '<div class="error">File upload failed</div>';
            }
        }
    }
}

// Fetch submitted assignments
$submitted_assignments = [];
$stmt = $conn->prepare("
    SELECT s.submission_id, s.file_path, s.submitted_at, a.title AS assignment_title, c.title AS course_name
    FROM assignment_submissions s
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN courses c ON a.course_id = c.course_id
    WHERE s.student_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $submitted_assignments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assignment Submission | Student</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 900px; margin: 30px auto; padding: 25px; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); }
        header { text-align: center; margin-bottom: 30px; }
        h1 { color:rgb(255, 255, 255); margin-bottom: 10px; }
        .form-container { background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e9ecef; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; }
        select, input[type="file"] { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 16px; background: white; }
        button { background: #3498db; color: white; border: none; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; transition: background 0.3s; }
        button:hover { background: #2980b9; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        nav { background: #2c3e50; padding: 15px 0; margin-bottom: 30px; }
        nav ul { display: flex; justify-content: center; list-style: none; padding: 0; margin: 0; }
        nav ul li { margin: 0 15px; }
        nav ul li a { color: white; text-decoration: none; font-weight: 500; font-size: 16px; padding: 8px 15px; border-radius: 4px; transition: background 0.3s; }
        nav ul li a:hover { background: #3498db; }
        .logout-btn { background: #e74c3c !important; }
        .logout-btn:hover { background: #c0392b !important; }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="dashboard_student.php">Dashboard</a></li>
            <li><a href="courses_student.php">My Courses</a></li>
            <li><a href="assignments_students.php">Assignments</a></li>
            <li><a href="support_student.php">Support</a></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <header>
            <h1>Assignment Submission</h1>
            <p>Submit your assignments and quizzes here</p>
        </header>
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="course_id">Select Course:</label>
                    <select name="course_id" id="course_id" required onchange="this.form.submit()">
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['course_id'] ?>" 
                                <?= (isset($_POST['course_id']) && $_POST['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($assignments)): ?>
                <div class="form-group">
                    <label for="assignment_id">Select Assignment:</label>
                    <select name="assignment_id" id="assignment_id" required>
                        <option value="">-- Choose Assignment --</option>
                        <?php foreach ($assignments as $assignment): ?>
                            <option value="<?= $assignment['assignment_id'] ?>">
                                <?= htmlspecialchars($assignment['title']) ?> (Posted: <?= date('M d, Y', strtotime($assignment['uploaded_at'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="assignment">Upload Solution:</label>
                    <input type="file" name="assignment" id="assignment" required accept=".pdf,.doc,.docx,.zip">
                </div>
                
                <button type="submit">Submit Solution</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="assignment-list">
            <h2>Your Submitted Solutions</h2>
            
            <?php if (empty($submitted_assignments)): ?>
                <p>No solutions submitted yet.</p>
            <?php else: ?>
                <?php foreach ($submitted_assignments as $submission): ?>
                    <div class="assignment-item">
                        <div class="assignment-info">
                            <h3><?= htmlspecialchars($submission['course_name']) ?> - <?= htmlspecialchars($submission['assignment_title']) ?></h3>
                            <p>Submitted on: <?= date('M d, Y H:i', strtotime($submission['submitted_at'])) ?></p>
                        </div>
                        <div class="assignment-actions">
                            <a href="<?= htmlspecialchars($submission['file_path']) ?>" target="_blank">Download</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
