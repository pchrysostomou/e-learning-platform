<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    $_SESSION['error'] = "User not specified.";
    header("Location: " . base_url("admin/users.php"));
    exit;
}

// Fetch user from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: " . base_url("admin/users.php"));
    exit;
}

// Pre-fill form fields
$name = $user['name'];
$email = $user['email'];
$role = $user['role'];
$is_active = (int)($user['is_active'] ?? 0);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'] ?? 'student';
    $is_active = isset($_POST['active']) ? 1 : 0;

    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $email, $role, $is_active, $user_id]);

        log_activity($_SESSION['user_id'], "Updated user #$user_id ($email)");
        $_SESSION['success'] = "✅ User updated successfully.";
        header("Location: " . base_url("admin/user_profile.php?id=$user_id"));
        exit;
    }
}

$page_title = "Edit User";
include '../templates/header.php';
?>

<div class="container-fluid p-4">
    <h2 class="mb-4">✏️ Edit User</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="card card-body shadow-sm" style="max-width: 600px;">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select" required>
                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="teacher" <?= $role === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="active" class="form-check-input" id="active" <?= $is_active ? 'checked' : '' ?>>
            <label class="form-check-label" for="active">Account is active</label>
        </div>

        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        <a href="<?= base_url("admin/user_profile.php?id=$user_id") ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include '../templates/footer.php'; ?>
