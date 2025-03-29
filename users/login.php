<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$email = $password = '';
$errors = [];

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email)) $errors[] = "Email is required.";
    if (empty($password)) $errors[] = "Password is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'] ?? 'student';

            // Optional: Remember Me
            if (!empty($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], hash('sha256', $token), $expires]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            }

            // Redirect based on role
            switch ($_SESSION['user_role']) {
                case 'admin':
                    header("Location: " . base_url("admin/dashboard.php"));
                    break;
                case 'teacher':
                    header("Location: " . base_url("teacher/dashboard.php"));
                    break;
                default:
                    header("Location: " . base_url("users/dashboard.php"));
                    break;
            }
            exit;
        } else {
            $errors[] = "Incorrect email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | E-Learning</title>
    <link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/style.css') ?>">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-3">🔐 Login</h3>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="remember" class="form-check-input" id="remember">
                <label for="remember" class="form-check-label">Remember me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">➡️ Login</button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= base_url('users/forgot_password.php') ?>">Forgot password?</a><br>
            <small>Don't have an account? <a href="<?= base_url('users/register.php') ?>">Register</a></small>
        </div>
    </div>
</div>

<script src="<?= base_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
