<?php
ob_start(); // catch any unwanted output early
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ✅ Protect: must be logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$file_id = $_GET['file_id'] ?? null;

if (!$file_id || !is_numeric($file_id)) {
    http_response_code(400);
    exit('Invalid request.');
}

// ✅ Fetch file from DB
$stmt = $pdo->prepare("SELECT * FROM module_materials WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$filepath = __DIR__ . '/../uploads/materials/' . $file['file_name'];

// ✅ Check if file exists on server
if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File missing.');
}

// ✅ Block student if file is teacher-only
if (
    isset($_SESSION['user_role']) &&
    $_SESSION['user_role'] === 'student' &&
    !empty($file['is_teacher_only']) &&
    $file['is_teacher_only'] == 1
) {
    http_response_code(403);
    exit('You do not have access to this file.');
}

// ✅ Increment download count
$pdo->prepare("UPDATE module_materials SET download_count = download_count + 1 WHERE id = ?")
    ->execute([$file_id]);

// ✅ Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}
clearstatcache(true, $filepath);

// ✅ Serve file for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name'] ?: $file['file_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));

error_log("DEBUG: Starting file download for file ID $file_id, user ID: {$_SESSION['user_id']}, role: {$_SESSION['user_role']}");
readfile($filepath);
exit;
