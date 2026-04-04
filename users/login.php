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
                $expires = date('d-m-Y H:i:s', strtotime('+30 days'));
                $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], hash('sha256', $token), $expires]);
                setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), "/", "", false, true);
            }

            switch ($_SESSION['user_role']) {
                case 'admin':   header("Location: " . base_url("admin/dashboard.php")); break;
                case 'teacher': header("Location: " . base_url("teacher/dashboard.php")); break;
                default:        header("Location: " . base_url("users/dashboard.php")); break;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | EduLearn</title>

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

            <h2 class="auth-form-title">Welcome back</h2>
            <p class="auth-form-subtitle">Sign in to continue your learning journey</p>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
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
                               required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Password</label>
                        <a href="<?= base_url('users/forgot_password.php') ?>"
                           style="font-size:0.8rem;color:#6366f1;text-decoration:none;font-weight:600;">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-group mt-1">
                        <span class="input-group-text" style="background:#f8fafc;border:1.5px solid #e2e8f0;border-right:none;border-radius:8px 0 0 8px;color:#94a3b8;">
                            <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                        </span>
                        <input type="password" name="password" id="passwordInput" class="form-control"
                               placeholder="••••••••"
                               style="border-left:none!important;border-radius:0 8px 8px 0!important;"
                               required>
                        <button type="button" class="input-group-text" id="togglePwd"
                                style="background:#f8fafc;border:1.5px solid #e2e8f0;border-left:none;border-radius:0 8px 8px 0;cursor:pointer;color:#94a3b8;">
                            <i class="fas fa-eye" style="font-size:0.85rem;"></i>
                        </button>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label for="remember" class="form-check-label" style="font-size:0.875rem;color:#475569;">
                        Keep me signed in for 30 days
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                </button>
            </form>

            <div class="auth-footer-link">
                Don't have an account?
                <a href="<?= base_url('users/register.php') ?>">Create one free</a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script>
// Toggle password visibility
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
