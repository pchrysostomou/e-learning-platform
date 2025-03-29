<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$page_title = "Activity Log";

// Filters
$search = $_GET['search'] ?? '';
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Count total
$count_sql = "SELECT COUNT(*) FROM user_activity_log l JOIN users u ON u.id = l.user_id WHERE u.name LIKE :search OR l.activity LIKE :search";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(['search' => "%$search%"]);
$total_logs = $count_stmt->fetchColumn();
$total_pages = ceil($total_logs / $per_page);

// Fetch logs
$sql = "SELECT l.*, u.name FROM user_activity_log l
        JOIN users u ON u.id = l.user_id
        WHERE u.name LIKE :search OR l.activity LIKE :search
        ORDER BY l.created_at DESC
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute(['search' => "%$search%"]);
$logs = $stmt->fetchAll();
?>

<?php include '../templates/header.php'; ?>

<h2 class="mb-4">🕵️ User Activity Log</h2>

<form method="GET" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or activity..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">Search</button>
    </div>
</form>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>User</th>
            <th>Activity</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($logs) > 0): ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                <td>
                <a href="<?= base_url('admin/user_profile.php?id=' . $log['user_id']) ?>">
                <?= htmlspecialchars($log['name']) ?>
                </a>
                </td>
                    <td><?= htmlspecialchars($log['activity']) ?></td>
                    <td class="text-muted"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted">No activity found.</td></tr>
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