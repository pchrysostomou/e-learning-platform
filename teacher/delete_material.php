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
$material_id = $_GET['id'] ?? null;
$course_id = $_GET['course_id'] ?? null;
$module_id = $_GET['module_id'] ?? null;

if (!$material_id || !$course_id || !$module_id) {
    $_SESSION['error'] = "Missing parameters.";
    header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
    exit;
}

// ✅ Ensure the teacher owns the course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Access denied.";
    header("Location: " . base_url("teacher/modules.php?course_id=$course_id"));
    exit;
}

// ✅ Get material
$stmt = $pdo->prepare("SELECT * FROM module_materials WHERE id = ? AND module_id = ?");
$stmt->execute([$material_id, $module_id]);
$material = $stmt->fetch();

if (!$material) {
    $_SESSION['error'] = "Material not found.";
    header("Location: " . base_url("teacher/edit_module.php?id=$module_id&course_id=$course_id"));
    exit;
}

// ✅ Delete file from disk
$file_path = __DIR__ . '/../uploads/materials/' . $material['file_name'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// ✅ Delete from database
$stmt = $pdo->prepare("DELETE FROM module_materials WHERE id = ?");
$stmt->execute([$material_id]);

log_activity($teacher_id, "Deleted material '{$material['file_name']}' from module ID $module_id");

$_SESSION['success'] = "🗑 Material deleted.";
header("Location: " . base_url("teacher/edit_module.php?id=$module_id&course_id=$course_id"));
exit;
