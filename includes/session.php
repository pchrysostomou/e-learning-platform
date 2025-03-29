<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Redirect if user is not logged in
function redirect_if_not_logged_in() {
    if (empty($_SESSION['user_id'])) {
        header("Location: " . base_url("users/login.php"));
        exit;
    }
}

// ✅ Redirect if user is not admin
function redirect_if_not_admin() {
    if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header("Location: " . base_url("index.php"));
        exit;
    }
}
