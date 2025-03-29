<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';
$errors = [];
$success = '';
$reset = false;

if (!empty($token)) {
    // ✅ Retrieve token record
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    // ✅ Check if token exists and hasn't expired
    if ($row && strtotime($row['expires_at']) > time()) {
        $reset = $row;
    } else {
        $errors[] = "Invalid or expired reset token.";
    }

    // ✅ Handle reset form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
        $password = trim($_POST['password']);
        $confirm  = trim($_POST['confirm_password']);

        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $errors[] = "Passwords do not match.";
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // ✅ Update user password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $reset['email']]);

            // ✅ Delete used reset token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$reset['email']]);

            $success = "✅ Password reset successful. <a href='" . base_url("users/login.php") . "'>Click here to login</a>.";
        }
    }
} else {
    $errors[] = "No reset token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="max-width: 450px; width: 100%;">
        <h3 class="text-center mb-4">🔐 Reset Password</h3>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($reset): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success w-100">🔄 Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include '../templates/footer.php'; ?>
