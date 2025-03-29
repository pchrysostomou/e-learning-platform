<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$course_id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($course_id) {
    // Check if course exists
    $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if ($course) {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);

        $_SESSION['success'] = "✅ Course deleted successfully.";
    } else {
        $_SESSION['error'] = "❌ Course not found.";
    }
} else {
    $_SESSION['error'] = "⚠️ Invalid course ID.";
}

header("Location: " . base_url("admin/courses.php"));
exit;
