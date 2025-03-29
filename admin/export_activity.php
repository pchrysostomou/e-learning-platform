<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['user_id'])) {
    die("Missing user ID.");
}

$user_id = $_GET['user_id'];

$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

$filename = "activity_" . strtolower($user['name']) . "_" . date("Ymd") . ".csv";

header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");
fputcsv($output, ['Activity', 'Date']);

$stmt = $pdo->prepare("SELECT activity, created_at FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();

foreach ($logs as $log) {
    fputcsv($output, [$log['activity'], $log['created_at']]);
}

fclose($output);
exit;
