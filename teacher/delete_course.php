<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("index.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "No course ID provided.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Ensure course belongs to this teacher
$stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if ($course) {
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$course_id, $teacher_id]);
    $_SESSION['success'] = "✅ Course deleted successfully.";
} else {
    $_SESSION['error'] = "❌ Course not found or access denied.";
}

header("Location: " . base_url("teacher/my_courses.php"));
exit;