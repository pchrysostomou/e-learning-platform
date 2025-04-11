<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

$user_role = $_SESSION['user_role'] ?? 'guest';
?>

<!-- Sidebar Navigation -->
<div class="list-group list-group-flush">
    <a href="<?= base_url('index.php') ?>" class="list-group-item list-group-item-action <?= is_active('index.php') ?>">🏠 Home</a>

    <?php if ($user_role === 'admin'): ?>
        <a href="<?= base_url('admin/dashboard.php') ?>" class="list-group-item list-group-item-action <?= is_active('admin/dashboard.php') ?>">🧑‍💼 Admin Dashboard</a>
        <a href="<?= base_url('admin/users.php') ?>" class="list-group-item list-group-item-action <?= is_active('admin/users.php') ?>">👥 Manage Users</a>
        <a href="<?= base_url('admin/courses.php') ?>" class="list-group-item list-group-item-action <?= is_active('admin/courses.php') ?>">📚 Manage Courses</a>
        <a href="<?= base_url('admin/activity_log.php') ?>" class="list-group-item list-group-item-action <?= is_active('admin/activity_log.php') ?>">🕵️ Activity Log</a>

    <?php elseif ($user_role === 'teacher'): ?>
        <a href="<?= base_url('teacher/dashboard.php') ?>" class="list-group-item list-group-item-action <?= is_active('teacher/dashboard.php') ?>">🎓 Teacher Dashboard</a>
        <a href="<?= base_url('teacher/create_course.php') ?>" class="list-group-item list-group-item-action <?= is_active('teacher/create_course.php') ?>">➕ Create Course</a>
        <a href="<?= base_url('teacher/my_courses.php') ?>" class="list-group-item list-group-item-action <?= is_active('teacher/my_courses.php') ?>">📘 My Courses</a>
        <a href="<?= base_url('teacher/quiz_list.php') ?>" class="list-group-item list-group-item-action <?= is_active('teacher/quiz_list.php') ?>">📋 My Quizzes</a>
        <a href="<?= base_url('teacher/question_bank.php') ?>" class="list-group-item list-group-item-action <?= is_active('teacher/question_bank.php') ?>">📚 Question Bank</a>
    <?php elseif ($user_role === 'student'): ?>
        <a href="<?= base_url('users/dashboard.php') ?>" class="list-group-item list-group-item-action <?= is_active('users/dashboard.php') ?>">📖 Student Dashboard</a>
        <a href="<?= base_url('users/courses.php') ?>" class="list-group-item list-group-item-action <?= is_active('users/courses.php') ?>">🎓 Browse Courses</a>
        <a href="<?= base_url('users/available_quizzes.php') ?>" class="list-group-item list-group-item-action <?= is_active('users/available_quizzes.php') ?>">📝 Take a Quiz</a>
        <a href="<?= base_url('users/my_scores.php') ?>" class="list-group-item list-group-item-action <?= is_active('users/my_scores.php') ?>">📊 My Scores</a>
    <?php endif; ?>

    <a href="<?= base_url('users/account.php') ?>" class="list-group-item list-group-item-action <?= is_active('users/account.php') ?>">⚙️ My Account</a>
    <a href="<?= base_url('users/logout.php') ?>" class="list-group-item list-group-item-action text-danger">🚪 Logout</a>
</div>
