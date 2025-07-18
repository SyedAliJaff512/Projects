<?php
require 'session_check.php';
require 'db.php';

if($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$courses = $conn->query("SELECT * FROM courses 
    WHERE course_id IN (
        SELECT course_id FROM enrollments 
        WHERE user_id=$user_id AND completion_status='completed'
    )");

// Generate PDF certificate (example using FPDF)
require('fpdf/fpdf.php');

if(isset($_GET['download'])) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(40,10,'Course Completion Certificate');
    $pdf->Output('D','certificate.pdf');
    exit();
}
