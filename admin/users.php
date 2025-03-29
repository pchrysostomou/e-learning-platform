<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$params = [];
$where_sql = "";

if (!empty($search)) {
    $where_sql = "WHERE name LIKE ? OR email LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total users
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where_sql");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Build query for users (we must inject LIMIT and OFFSET directly)
$sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = "Manage Users";
include '../templates/header.php';
?>

<h2 class="mb-4">👥 Manage Users</h2>

<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by name or email...">
        <button class="btn btn-outline-primary">Search</button>
    </div>
</form>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created</th>
            <th style="width: 120px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= ucfirst($user['role']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                <td>
                    <a href="<?= base_url('admin/edit_user.php?id=' . $user['id']) ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="<?= base_url('admin/delete_user.php?id=' . $user['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($users) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted">No users found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>
