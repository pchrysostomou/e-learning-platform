<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

$name = $email = $password = $confirm_password = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = "Email is already registered.";

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);

        send_email($_ENV['SMTP_FROM_EMAIL'], 'New User Registration', "<p><strong>Name:</strong> $name<br><strong>Email:</strong> $email</p>");

        $_SESSION['success'] = "Registration successful. You can now sign in.";
        header("Location: " . base_url("users/login.php"));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | EduLearn</title>

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
        <h1>Join EduLearn</h1>
        <p class="auth-tagline">Start your learning journey today. Free access to all courses and quizzes.</p>

        <ul class="auth-features">
            <li>
                <div class="feat-icon"><i class="fas fa-user-plus"></i></div>
                Free account — no credit card
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-book-open"></i></div>
                Enroll in any available course
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-chart-line"></i></div>
                Monitor your quiz progress
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-trophy"></i></div>
                Climb the leaderboard
            </li>
            <li>
                <div class="feat-icon"><i class="fas fa-file-export"></i></div>
                Export scores as PDF or CSV
            </li>
        </ul>
    </div>

    <!-- ===== RIGHT PANEL ===== -->
    <div class="auth-panel-right">
        <div style="width:100%;max-width:380px;">

            <!-- Mobile logo -->
            <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span style="font-weight:800;font-size:1.1rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">EduLearn</span>
            </div>

            <h2 class="auth-form-title">Create your account</h2>
            <p class="auth-form-subtitle">It's free and only takes a minute</p>

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
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                            <i class="fas fa-user" style="font-size:0.85rem;"></i>
                        </span>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($name) ?>"
                               placeholder="John Doe"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                            <i class="fas fa-envelope" style="font-size:0.85rem;"></i>
                        </span>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($email) ?>"
                               placeholder="you@example.com"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                            <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                        </span>
                        <input type="password" name="password" id="passwordInput" class="form-control"
                               placeholder="Min. 8 characters"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required>
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
                               placeholder="Repeat password"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="auth-footer-link">
                Already have an account?
                <a href="<?= base_url('users/login.php') ?>">Sign in</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function () {
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
</script>
</body>
</html>
