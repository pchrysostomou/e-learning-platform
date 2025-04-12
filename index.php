<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header("Location: " . base_url("admin/dashboard.php"));
            break;
        case 'teacher':
            header("Location: " . base_url("teacher/dashboard.php"));
            break;
        case 'student':
        default:
            header("Location: " . base_url("users/dashboard.php"));
            break;
    }
    exit;
}

header("Location: " . base_url("users/login.php"));
exit;
