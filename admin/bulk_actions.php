<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_ids']) && is_array($_POST['user_ids'])) {
    $ids = array_map('intval', $_POST['user_ids']);

    if ($_POST['action'] === 'delete') {
        $in = implode(',', array_fill(0, count($ids), '?'));

        // Prevent admin deletion
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($in) AND role != 'admin'");
        $stmt->execute($ids);

        $_SESSION['success'] = "✅ Selected users deleted.";
    }
}

header("Location: " . base_url('admin/users.php'));
exit;
