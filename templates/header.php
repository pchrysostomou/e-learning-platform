<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'User';

// Fetch profile pic
$pic = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $pic = $stmt->fetchColumn();
}
$profile_pic = base_url('uploads/profile_pics/' . ($pic ?: 'default.png'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'E-Learning Platform' ?></title>
    <link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="flex-grow-1">
<div class="container-fluid">
    <!-- Topbar -->
    <div class="topbar d-flex justify-content-between align-items-center p-2 bg-dark text-white">
        <div><strong>E-Learning Platform</strong></div>
        <div class="dropdown">
            <a class="btn btn-outline-light dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                <img src="<?= $profile_pic ?>" class="rounded-circle me-2" alt="Profile" width="30" height="30">
                <?= htmlspecialchars($user_name) ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= base_url($user_role . '/dashboard.php') ?>">📊 Dashboard</a></li>
                <li><a class="dropdown-item" href="<?= base_url('users/account.php') ?>">⚙️ My Account</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= base_url('users/logout.php') ?>">🚪 Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Page layout -->
    <div class="row flex-fill">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 bg-light p-0 sidebar">
            <?php include '../templates/sidebar.php'; ?>
        </div>

        <!-- Page content starts here -->
        <div class="col-md-9 col-lg-10 p-4">