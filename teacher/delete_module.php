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
$module_id = $_GET['id'] ?? null;
$course_id = $_GET['course_id'] ?? null;

if (!$module_id || !$course_id) {
    $_SESSION['error'] = "Missing parameters.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Ensure the module belongs to the teacher’s course
$stmt = $pdo->prepare("
    SELECT m.id
    FROM modules m
    JOIN courses c ON c.id = m.course_id
    WHERE m.id = ? AND c.id = ? AND c.teacher_id = ?
");
$stmt->execute([$module_id, $course_id, $teacher_id]);
$module = $stmt->fetch();

if ($module) {
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ? AND course_id = ?");
    $stmt->execute([$module_id, $course_id]);
    $_SESSION['success'] = "✅ Module deleted successfully.";
} else {
    $_SESSION['error'] = "❌ Module not found or access denied.";
}

header("Location: " . base_url("teacher/modules.php?course_id=" . $course_id));
exit;
