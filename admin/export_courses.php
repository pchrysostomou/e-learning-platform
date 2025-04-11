<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['user_id'])) {
    die("Missing user ID.");
}

$user_id = $_GET['user_id'];

// Get user role
$stmt = $pdo->prepare("SELECT role, name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$filename = "courses_" . strtolower($user['name']) . "_" . date("Ymd") . ".csv";

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");
fputcsv($output, ['Course Title', 'Created At']);

if ($user['role'] === 'student') {
    $sql = "SELECT c.title, e.created_at 
            FROM enrollments e 
            JOIN courses c ON c.id = e.course_id 
            WHERE e.user_id = ?";
} elseif ($user['role'] === 'teacher') {
    $sql = "SELECT title, created_at FROM courses WHERE teacher_id = ?";
} else {
    $sql = "SELECT title, created_at FROM courses ORDER BY created_at DESC LIMIT 50";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();

foreach ($courses as $row) {
    fputcsv($output, [$row['title'], $row['created_at']]);
}

fclose($output);
exit;
