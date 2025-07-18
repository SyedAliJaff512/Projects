<?php
require 'session_check.php';
require 'db.php';

if ($_SESSION['role'] !== 'user' && $_SESSION['role'] !== 'teacher') {
    header("Location: dashboard_{$_SESSION['role']}.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = $success = '';
$teacher_id = '';

// Fetch teacher_id if role is teacher
if ($_SESSION['role'] === 'teacher') {
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($teacher_id);
    $stmt->fetch();
    $stmt->close();
}

// Step 1: Teacher registration (personal info)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_teacher'])) {
    $fullname = trim($_POST['fullname']);
    $qualification = trim($_POST['qualification']);
    $experience = (int)$_POST['experience'];
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    // Server-side validation including phone number constraint
    if ($fullname === '' || $qualification === '' || $experience < 0 || $contact === '' || $address === '') {
        $error = "All fields are required and experience must be a non-negative number.";
    } elseif (!preg_match('/^\d{11}$/', $contact)) {
        $error = "Phone number must be exactly 11 digits.";
    } else {
        // Generate unique teacher_id
        $result = $conn->query("SELECT MAX(teacher_id) AS maxid FROM teachers");
        $row = $result->fetch_assoc();
        $maxid = ($row && $row['maxid']) ? (int)filter_var($row['maxid'], FILTER_SANITIZE_NUMBER_INT) : 0;
        $teacher_id = 'TCH' . str_pad($maxid + 1, 4, '0', STR_PAD_LEFT);

        // Prevent duplicate registration
        $check = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "You are already registered as a teacher.";
        } else {
            $stmt = $conn->prepare("INSERT INTO teachers (teacher_id, user_id, fullname, qualification, experience_years, contact, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sississ", $teacher_id, $user_id, $fullname, $qualification, $experience, $contact, $address);
            if ($stmt->execute()) {
                $conn->query("UPDATE users SET role='teacher' WHERE user_id=$user_id");
                $_SESSION['role'] = 'teacher';
                $success = "Teacher registration successful! Now select up to 2 courses to teach.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Step 2: Course selection (after teacher registration)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_courses'])) {
    // Always fetch teacher_id from DB
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($teacher_id);
    $stmt->fetch();
    $stmt->close();

    $selected_courses = $_POST['courses'] ?? [];

    if (!$teacher_id) {
        $error = "Session expired. Please log in again.";
    } elseif (count($selected_courses) === 0) {
        $error = "Please select at least one course to teach.";
    } elseif (count($selected_courses) > 2) {
        $error = "You can select a maximum of 2 courses only.";
    } else {
        $conn->begin_transaction();
        try {
            $insert_course_stmt = $conn->prepare("INSERT INTO teacher_courses (teacher_id, course_id) VALUES (?, ?)");
            foreach ($selected_courses as $course_id) {
                $course_id = (int)$course_id;
                $insert_course_stmt->bind_param("si", $teacher_id, $course_id);
                if (!$insert_course_stmt->execute()) {
                    throw new Exception("Course assignment failed: " . $insert_course_stmt->error);
                }
            }
            $insert_course_stmt->close();
            $conn->commit();
            $success = "Courses assigned successfully! Redirecting to your dashboard...";
            header("Refresh:3; url=dashboard_teacher.php");
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Course assignment failed: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch all available courses for selection
$courses = [];
$res = $conn->query("SELECT course_id, title FROM courses");
while ($row = $res->fetch_assoc()) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Registration | E-Courses System</title>
    <link rel="stylesheet" href="style.css">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const courseSelect = document.getElementById('courses');
        if (courseSelect) {
            courseSelect.addEventListener('change', function() {
                const selected = Array.from(this.selectedOptions);
                if (selected.length > 2) {
                    alert("You can select a maximum of 2 courses only.");
                    selected[selected.length-1].selected = false;
                }
            });
        }
    });
    </script>
    <style>
        body { background: #f5f7fa; font-family: Segoe UI, Arial, sans-serif; margin: 0; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 2.5rem 2rem; }
        h1 { color: #004080; text-align: center; margin-bottom: 1.5rem; }
        nav { text-align: center; margin-bottom: 2rem; }
        nav a { color: #004080; text-decoration: none; margin: 0 1rem; font-weight: 500; }
        nav a:hover { text-decoration: underline; }
        label { font-weight: 600; color: #333; display: block; margin-top: 1rem; margin-bottom: 0.3rem; }
        input, select { width: 100%; padding: 0.7rem; border-radius: 6px; border: 1px solid #cfd8dc; font-size: 1rem; }
        select[multiple] { height: 110px; }
        button { background: #0073e6; color: #fff; border: none; padding: 0.8rem 1.5rem; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 1.2rem; }
        button:hover { background: #004080; }
        .success { background: #e6ffea; color: #008a3a; border: 1px solid #b3ffd3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #ffe5e5; color: #c00; border: 1px solid #ffb3b3; padding: 0.7rem 1rem; border-radius: 4px; margin-bottom: 1rem; }
        footer { text-align: center; color: #888; font-size: 0.95rem; margin-top: 2rem; }
        @media (max-width: 600px) {
            .container { padding: 1rem; }
            select[multiple] { height: 80px; }
        }
    </style>
</head>
<body>
    <header><h1>Teacher Registration</h1></header>
    <nav>
        <a href="dashboard_user.php">Dashboard</a>
        <a href="register_teacher.php">Register as Teacher</a>
        <a href="logout.php" style="color:#c00;">Logout</a>
    </nav>
    <div class="container">
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

        <?php if (!$success && $_SESSION['role'] === 'user'): ?>
        <!-- Step 1: Teacher Registration Form -->
        <form method="POST" autocomplete="off">
            <label for="fullname">Full Name:</label>
            <input type="text" name="fullname" id="fullname" required>

            <label for="qualification">Qualification:</label>
            <input type="text" name="qualification" id="qualification" required>

            <label for="experience">Experience (years):</label>
            <input type="number" name="experience" id="experience" min="0" required>

            <label for="contact">Contact:</label>
            <input type="text" name="contact" id="contact" required pattern="\d{11}" maxlength="11" title="Phone number must be exactly 11 digits">

            <label for="address">Address:</label>
            <input type="text" name="address" id="address" required>

            <button type="submit" name="register_teacher">Register as Teacher</button>
        </form>
        <?php endif; ?>

        <?php if ($_SESSION['role'] === 'teacher' && $teacher_id && !$success): ?>
        <!-- Step 2: Course Selection Form -->
        <form method="POST" autocomplete="off">
            <label for="courses">Select Courses to Teach (max 2):</label>
            <select name="courses[]" id="courses" multiple required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="select_courses">Assign Courses</button>
        </form>

         <!-- Step 2: Course Selection Form -->
        <form method="POST" autocomplete="off">
            <label for="courses">Select Courses to Teach (max 2):</label>
            <select name="courses[]" id="courses" multiple required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="select_courses">Assign Courses</button>
        </form>
        <?php endif; ?>

        <p style="text-align:center; margin-top:1rem;">
            <a href="dashboard_user.php" class="btn">Back to User Dashboard</a>
        </p>
    </div>
    <footer>
        &copy; <?= date('Y'); ?> E-Courses System
    </footer>
</body>
</html>
