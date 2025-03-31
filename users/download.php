<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$file_id = $_GET['file_id'] ?? null;

if (!$file_id || !is_numeric($file_id)) {
    http_response_code(400);
    exit('Invalid request.');
}

// Fetch file from DB
$stmt = $pdo->prepare("SELECT * FROM module_materials WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$filepath = __DIR__ . '/../uploads/materials/' . $file['filename'];

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File missing.');
}

// Increment download count
$pdo->prepare("UPDATE module_materials SET download_count = download_count + 1 WHERE id = ?")->execute([$file_id]);

// Serve file for download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file['original_name'] ?: $file['filename']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
