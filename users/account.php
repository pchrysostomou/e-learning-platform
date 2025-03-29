<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];
$success = '';
$errors = [];

// Fetch user
$stmt = $pdo->prepare("SELECT name, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Delete profile picture
if (isset($_POST['delete_pic'])) {
    $old_pic = $user['profile_pic'];
    $path = __DIR__ . '/../uploads/profile_pics/' . $old_pic;

    if ($old_pic && file_exists($path)) {
        unlink($path);
    }

    $pdo->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?")->execute([$user_id]);
    $success = "🗑 Profile picture deleted.";
    $user['profile_pic'] = null;
    log_activity($user_id, "Deleted profile picture.");
}

// Upload new profile picture
if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];

    if ($file['error'] === 0 && $file['size'] < 2 * 1024 * 1024) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($ext, $allowed)) {
            $filename = 'user_' . $user_id . '.' . $ext;
            $target = __DIR__ . '/../uploads/profile_pics/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
                $user['profile_pic'] = $filename;
                $success = "✅ Profile picture updated.";
                log_activity($user_id, "Updated profile picture.");
            } else {
                $errors[] = "Failed to save uploaded file.";
            }
        } else {
            $errors[] = "Only JPG, PNG, GIF files allowed.";
        }
    } else {
        $errors[] = "Image must be under 2MB.";
    }
}

// Update name & email
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $success = "✅ Profile updated.";
        log_activity($user_id, "Updated name/email.");
    }
}

// Update password
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $stored = $stmt->fetchColumn();

    if (!password_verify($current, $stored)) {
        $errors[] = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    } elseif ($new !== $confirm) {
        $errors[] = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $user_id]);
        $success = "✅ Password updated.";
        log_activity($user_id, "Changed password.");
    }
}

$page_title = "My Account";
include '../templates/header.php';
?>

<!-- Main Content -->
<h2 class="mb-4">⚙️ My Account</h2>

<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
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

<!-- Profile Picture -->
<div class="mb-4 d-flex align-items-center gap-4">
    <div>
        <img src="<?= base_url('uploads/profile_pics/' . ($user['profile_pic'] ?? 'default.png')) ?>"
             id="profile-img" alt="Profile Picture" width="80" height="80" class="rounded-circle border">
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label class="form-label mb-1">Change Profile Picture</label>
        <div class="input-group">
            <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/*" required>
            <button type="submit" name="upload_pic" value="1" class="btn btn-outline-secondary">Upload</button>
        </div>
    </form>

    <?php if (!empty($user['profile_pic'])): ?>
        <form method="POST" class="ms-2">
            <input type="hidden" name="delete_pic" value="1">
            <button type="submit" class="btn btn-sm btn-danger">🗑 Delete</button>
        </form>
    <?php endif; ?>
</div>

<!-- Profile Info and Password -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">📝 Update Profile</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">💾 Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">🔐 Change Password</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" name="update_password" class="btn btn-warning">🔄 Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Activity Log -->
<hr class="my-5">
<h5 class="mb-3">🕒 Activity Log</h5>

<?php
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM user_activity_log WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $per_page);

$stmt = $pdo->prepare("SELECT * FROM user_activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute([$user_id]);
$logs = $stmt->fetchAll();
?>

<ul class="list-group">
    <?php foreach ($logs as $log): ?>
        <li class="list-group-item d-flex justify-content-between">
            <span><?= htmlspecialchars($log['activity']) ?></span>
            <span class="text-muted small"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></span>
        </li>
    <?php endforeach; ?>
</ul>

<?php if ($total_pages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<script>
document.getElementById('profile_pic')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('preview-img');
    if (file && preview) {
        preview.style.display = 'block';
        preview.src = URL.createObjectURL(file);
    }
});
</script>

<?php include '../templates/footer.php'; ?>
