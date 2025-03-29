<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    http_response_code(403);
    exit;
}

$student_id = $_SESSION['user_id'];
$module_id = $_POST['module_id'] ?? null;
$completed = $_POST['completed'] ?? 0;

if (!$module_id) {
    http_response_code(400);
    exit;
}

if ($completed) {
    // Insert if not already marked
    $stmt = $pdo->prepare("INSERT IGNORE INTO module_progress (student_id, module_id) VALUES (?, ?)");
    $stmt->execute([$student_id, $module_id]);
} else {
    // Remove if unchecked
    $stmt = $pdo->prepare("DELETE FROM module_progress WHERE student_id = ? AND module_id = ?");
    $stmt->execute([$student_id, $module_id]);
}
 