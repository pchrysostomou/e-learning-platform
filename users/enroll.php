<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'student') {
    header("Location: " . base_url("index.php"));
    exit;
}

$student_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "Invalid course selected.";
    header("Location: " . base_url("users/courses.php"));
    exit;
}

// Check if already enrolled
$stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?");
$stmt->execute([$student_id, $course_id]);
$already_enrolled = $stmt->fetchColumn();

if ($already_enrolled) {
    $_SESSION['error'] = "You are already enrolled in this course.";
    header("Location: " . base_url("users/courses.php"));
    exit;
}

// Enroll student
$stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
$stmt->execute([$student_id, $course_id]);

$_SESSION['success'] = "✅ Successfully enrolled in the course!";
header("Location: " . base_url("users/courses.php"));
exit; 