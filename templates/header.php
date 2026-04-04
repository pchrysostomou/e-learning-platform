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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'E-Learning Platform') ?></title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="d-flex flex-column min-vh-100">
<div class="flex-grow-1">
<div class="container-fluid p-0">

    <!-- ===== TOPBAR ===== -->
    <div class="topbar d-flex justify-content-between align-items-center">
        <a href="<?= base_url('index.php') ?>" class="brand-logo text-decoration-none">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span class="brand-name">EduLearn</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Notification bell (decorative) -->
            <button class="btn btn-sm btn-outline-secondary border-0 position-relative" style="background:transparent;color:#64748b;padding:0.4rem 0.6rem;">
                <i class="fas fa-bell" style="font-size:0.95rem;"></i>
            </button>

            <!-- Profile dropdown -->
            <div class="dropdown">
                <a class="btn btn-outline-light dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= $profile_pic ?>" class="rounded-circle me-2" alt="Profile" width="30" height="30" style="object-fit:cover;">
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($user_name) ?></span>
                    <i class="fas fa-chevron-down ms-1" style="font-size:0.7rem;opacity:0.6;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= base_url($user_role . '/dashboard.php') ?>">
                            <i class="fas fa-chart-pie"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= base_url('users/account.php') ?>">
                            <i class="fas fa-user-circle"></i> My Account
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= base_url('users/logout.php') ?>">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== PAGE LAYOUT ===== -->
    <div class="row flex-fill g-0">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0 sidebar">
            <?php include '../templates/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <div class="col-md-9 col-lg-10">
