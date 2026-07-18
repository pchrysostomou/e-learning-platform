<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';
$errors = [];
$success = '';
$reset = false;

if (!empty($token)) {
    // Retrieve token record
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    // Check if token exists and hasn't expired
    if ($row && strtotime($row['expires_at']) > time()) {
        $reset = $row;
    } else {
        $errors[] = "Invalid or expired reset token.";
    }

    // Handle reset form submission
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

            // Update user password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed, $reset['email']]);

            // Delete used reset token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$reset['email']]);

            $success = "Password reset successful.";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | EduLearn</title>

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

            <h2 class="auth-form-title">Set a new password</h2>
            <p class="auth-form-subtitle">Choose a strong password for your account</p>

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

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
                <a href="<?= base_url('users/login.php') ?>" class="btn btn-primary w-100">
                    <i class="fas fa-arrow-right-to-bracket"></i> Continue to Sign In
                </a>
            <?php elseif ($reset): ?>
                <form method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                                <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                            </span>
                            <input type="password" name="password" id="passwordInput" class="form-control"
                                   placeholder="••••••••"
                                   style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                                   required autofocus>
                            <button type="button" class="input-group-text" id="togglePwd"
                                    style="background:#f8fafc;border:1.5px solid #e2e8f0;border-left:none;border-radius:0 8px 8px 0;cursor:pointer;color:#94a3b8;">
                                <i class="fas fa-eye" style="font-size:0.85rem;"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                                <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                            </span>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="••••••••"
                                   style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                                   required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-rotate"></i> Reset Password
                    </button>
                </form>
            <?php else: ?>
                <a href="<?= base_url('users/forgot_password.php') ?>" class="btn btn-primary w-100">
                    <i class="fas fa-paper-plane"></i> Request a new link
                </a>
            <?php endif; ?>

            <div class="auth-footer-link">
                Remembered your password?
                <a href="<?= base_url('users/login.php') ?>">Back to Sign In</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
// Toggle password visibility (only present on the reset form)
var togglePwd = document.getElementById('togglePwd');
if (togglePwd) {
    togglePwd.addEventListener('click', function () {
        const inp = document.getElementById('passwordInput');
        const icon = this.querySelector('i');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            inp.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
}
</script>
</body>
</html>
