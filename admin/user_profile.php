<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    $_SESSION['error'] = "No user ID provided.";
    header("Location: " . base_url("admin/users.php"));
    exit;
}

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: " . base_url("admin/users.php"));
    exit;
}

// Fetch activity logs
$log_stmt = $pdo->prepare("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$log_stmt->execute([$user_id]);
$logs = $log_stmt->fetchAll();

// Fetch courses depending on user role
if ($user['role'] === 'student') {
    $course_stmt = $pdo->prepare("
        SELECT c.title, c.id, e.created_at 
        FROM enrollments e 
        JOIN courses c ON c.id = e.course_id 
        WHERE e.user_id = ?
    ");
    $course_stmt->execute([$user_id]);
} elseif ($user['role'] === 'teacher') {
    $course_stmt = $pdo->prepare("SELECT id, title, created_at FROM courses WHERE teacher_id = ?");
    $course_stmt->execute([$user_id]);
} else {
    $course_stmt = $pdo->prepare("SELECT id, title, created_at FROM courses ORDER BY created_at DESC LIMIT 5");
    $course_stmt->execute();
}
$courses = $course_stmt->fetchAll();

$page_title = "User Profile";
include '../templates/header.php';
?>

<div class="container-fluid p-4">
    <h2 class="mb-4">👤 <?= htmlspecialchars($user['name']) ?>'s Profile</h2>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-3" id="userTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" role="tab">Profile Info</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" role="tab">Courses</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" role="tab">Activity Log</button>
        </li>
    </ul>

    <div class="tab-content" id="userTabContent">
        <!-- Profile Tab -->
        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="row mb-4">
                <div class="col-md-3">
                    <img src="<?= base_url('uploads/profile_pics/' . ($user['profile_pic'] ?? 'default.png')) ?>" 
                         class="img-fluid rounded-circle border" alt="Profile Picture">
                </div>
                <div class="col-md-9">
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Role:</strong> <?= ucfirst($user['role']) ?></p>
                    <p><strong>Joined:</strong> <?= date('Y-m-d', strtotime($user['created_at'])) ?></p>

                    <a href="<?= base_url("admin/edit_user.php?id=$user_id") ?>" class="btn btn-sm btn-primary">✏️ Edit</a>
                    <a href="<?= base_url("admin/delete_user.php?id=$user_id") ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?')">🗑 Delete</a>
                </div>
            </div>
        </div>

        <!-- Courses Tab -->
        <div class="tab-pane fade" id="courses" role="tabpanel" aria-labelledby="courses-tab">
            <div class="d-flex justify-content-between mb-2">
                <h5>📚 <?= $user['role'] === 'teacher' ? 'Created Courses' : 'Enrolled Courses' ?></h5>
                <a href="<?= base_url("admin/export_courses.php?user_id=$user_id") ?>" class="btn btn-outline-success btn-sm">⬇️ Export to CSV</a>
            </div>
            <?php if ($courses): ?>
                <ul class="list-group mb-4">
                    <?php foreach ($courses as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($c['title']) ?>
                            <span class="text-muted small"><?= date('Y-m-d', strtotime($c['created_at'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No courses found.</p>
            <?php endif; ?>
        </div>

        <!-- Activity Tab -->
        <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
            <div class="d-flex justify-content-between mb-2">
                <h5>🕒 Latest Activity</h5>
                <a href="<?= base_url("admin/export_activity.php?user_id=$user_id") ?>" class="btn btn-outline-primary btn-sm">⬇️ Export to CSV</a>
            </div>
            <?php if ($logs): ?>
                <ul class="list-group">
                    <?php foreach ($logs as $log): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($log['activity']) ?>
                            <span class="text-muted small"><?= date('d-m-Y H:i', strtotime($log['created_at'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No activity recorded.</p>
            <?php endif; ?>
        </div>
    </div>

    <a href="<?= base_url('admin/users.php') ?>" class="btn btn-secondary mt-4">← Back to Users</a>
</div>

<?php include '../templates/footer.php'; ?>
