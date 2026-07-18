<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$success = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32));
            $expires = date('d-m-Y H:i:s', time() + 1800); // 30 minutes

            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);

            $link = base_url("users/reset_password.php?token=$token");
            $body = "Click the link to reset your password: <a href='$link'>$link</a>";
            send_email($email, "Reset Your Password", $body);

            $success = "A password reset link has been sent to your email.";
        } else {
            $errors[] = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | EduLearn</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap -->
    <link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body class="auth-page">

<div class="auth-wrapper">

    <!-- ===== LEFT PANEL ===== -->
    <div class="auth-panel-left d-none d-lg-flex">
        <div class="auth-brand-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1>EduLearn</h1>
        <p class="auth-tagline">Your gateway to knowledge. Learn, grow, and achieve your goals with expert-led courses.</p>

        <ul class="auth-features">
            <li>
                <div class="feat-icon"><i class="fas fa-book-open"></i></div>
                Access hundreds of courses
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
                Track your progress in real-time
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-pen-to-square"></i></div>
                Take quizzes and earn scores
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-trophy"></i></div>
                Compete on the leaderboard
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-certificate"></i></div>
                Export your achievements
            </li>
        </ul>
    </div>

    <!-- ===== RIGHT PANEL ===== -->
    <div class="auth-panel-right">
        <div style="width:100%;max-width:360px;">

            <!-- Mobile logo -->
            <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span style="font-weight:800;font-size:1.1rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">EduLearn</span>
            </div>

            <h2 class="auth-form-title">Forgot your password?</h2>
            <p class="auth-form-subtitle">No worries — enter your email and we'll send you a reset link</p>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-circle-exclamation"></i>
                    <div>
                        <?php foreach ($errors as $e): ?>
                            <div><?= htmlspecialchars($e) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="mb-4">
                    <label class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                            <i class="fas fa-envelope" style="font-size:0.85rem;"></i>
                        </span>
                        <input type="email" name="email" class="form-control"
                               placeholder="you@example.com"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>

            <div class="auth-footer-link">
                Remembered your password?
                <a href="<?= base_url('users/login.php') ?>">Back to Sign In</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
