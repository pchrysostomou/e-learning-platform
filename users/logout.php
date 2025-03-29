<?php
session_start();
require_once '../includes/db.php';

// Delete token from database
if (!empty($_COOKIE['remember_token'])) {
    $hashed = hash('sha256', $_COOKIE['remember_token']);
    $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
    $stmt->execute([$hashed]);

    // Remove cookie
    setcookie('remember_token', '', time() - 3600, "/");
}

// Clear session
session_unset();
session_destroy();

// Redirect
header("Location: login.php");
exit; 
