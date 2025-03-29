<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$user_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Prevent changing admin status
$stmt = $pdo->prepare("SELECT role, is_active FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user && $user['role'] !== 'admin') {
    $new_status = $user['is_active'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
    $_SESSION['success'] = "User status updated.";
}

header("Location: " . base_url("admin/users.php"));
exit;
