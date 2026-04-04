<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/functions.php';
$user_role = $_SESSION['user_role'] ?? 'guest';
?>

<!-- ===== SIDEBAR NAVIGATION ===== -->
<nav class="d-flex flex-column h-100">

    <div class="list-group list-group-flush mt-1">

        <!-- Home -->
        <a href="<?= base_url('index.php') ?>"
           class="list-group-item list-group-item-action <?= is_active('index.php') ?>">
            <i class="fas fa-house"></i> Home
        </a>

        <?php if ($user_role === 'admin'): ?>

            <div class="sidebar-section">Administration</div>

            <a href="<?= base_url('admin/dashboard.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('admin/dashboard.php') ?>">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= base_url('admin/users.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('admin/users.php') ?>">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="<?= base_url('admin/courses.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('admin/courses.php') ?>">
                <i class="fas fa-book-open"></i> Manage Courses
            </a>
            <a href="<?= base_url('admin/activity_log.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('admin/activity_log.php') ?>">
                <i class="fas fa-clipboard-list"></i> Activity Log
            </a>

        <?php elseif ($user_role === 'teacher'): ?>

            <div class="sidebar-section">Teaching</div>

            <a href="<?= base_url('teacher/dashboard.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('teacher/dashboard.php') ?>">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= base_url('teacher/create_course.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('teacher/create_course.php') ?>">
                <i class="fas fa-circle-plus"></i> Create Course
            </a>
            <a href="<?= base_url('teacher/my_courses.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('teacher/my_courses.php') ?>">
                <i class="fas fa-book"></i> My Courses
            </a>
            <a href="<?= base_url('teacher/quiz_list.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('teacher/quiz_list.php') ?>">
                <i class="fas fa-list-check"></i> My Quizzes
            </a>
            <a href="<?= base_url('teacher/question_bank.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('teacher/question_bank.php') ?>">
                <i class="fas fa-database"></i> Question Bank
            </a>

        <?php elseif ($user_role === 'student'): ?>

            <div class="sidebar-section">Learning</div>

            <a href="<?= base_url('users/dashboard.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('users/dashboard.php') ?>">
                <i class="fas fa-gauge-high"></i> Dashboard
            </a>
            <a href="<?= base_url('users/courses.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('users/courses.php') ?>">
                <i class="fas fa-graduation-cap"></i> Browse Courses
            </a>
            <a href="<?= base_url('users/available_quizzes.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('users/available_quizzes.php') ?>">
                <i class="fas fa-pen-to-square"></i> Take a Quiz
            </a>
            <a href="<?= base_url('users/my_scores.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('users/my_scores.php') ?>">
                <i class="fas fa-chart-bar"></i> My Scores
            </a>
            <a href="<?= base_url('users/leaderboard.php') ?>"
               class="list-group-item list-group-item-action <?= is_active('users/leaderboard.php') ?>">
                <i class="fas fa-trophy"></i> Leaderboard
            </a>

        <?php endif; ?>

        <hr class="sidebar-hr">

        <div class="sidebar-section">Account</div>

        <a href="<?= base_url('users/account.php') ?>"
           class="list-group-item list-group-item-action <?= is_active('users/account.php') ?>">
            <i class="fas fa-user-gear"></i> My Account
        </a>
        <a href="<?= base_url('users/logout.php') ?>"
           class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>

    </div>
</nav>
