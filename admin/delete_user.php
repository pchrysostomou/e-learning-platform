<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$user_id = $_GET['id'] ?? null;

if ($user_id) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    log_activity($_SESSION['user_id'], "Deleted user #$user_id");
    $_SESSION['success'] = "✅ User deleted.";
} else {
    $_SESSION['error'] = "❌ User ID missing.";
}

header("Location: " . base_url("admin/users.php"));
exit;
