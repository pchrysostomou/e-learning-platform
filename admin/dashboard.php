<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Ensure only admin can access
redirect_if_not_logged_in();
redirect_if_not_admin();

// Page title
$page_title = "Admin Dashboard";

// Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

require_once '../templates/header.php';
?>

<h2 class="mb-4">📊 Admin Dashboard</h2>

<div class="row">
    <div class="col-md-3">
        <div class="card text-bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Users</h5>
                <p class="card-text fs-4"><?= $totalUsers ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Courses</h5>
                <p class="card-text fs-4"><?= $totalCourses ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Teachers</h5>
                <p class="card-text fs-4"><?= $totalTeachers ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Students</h5>
                <p class="card-text fs-4"><?= $totalStudents ?></p>
            </div>
        </div>
    </div>
</div>

<a href="users.php" class="btn btn-outline-primary mt-3">Manage Users</a>
<a href="courses.php" class="btn btn-outline-success mt-3">Manage Courses</a>

<?php require_once '../templates/footer.php'; ?>
