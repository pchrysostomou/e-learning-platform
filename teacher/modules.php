<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("index.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

if (!$course_id) {
    $_SESSION['error'] = "Course not specified.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// ✅ Verify course belongs to teacher
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Access denied or course not found.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// ✅ Build search and pagination logic
$params = [$course_id];
$where_sql = "WHERE course_id = ?";

if (!empty($search)) {
    $where_sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}

// ✅ Count total modules
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM modules $where_sql");
$count_stmt->execute($params);
$total_modules = $count_stmt->fetchColumn();
$total_pages = ceil($total_modules / $per_page);

// ✅ Fetch modules (LIMIT & OFFSET injected directly)
$limit_sql = "LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$query = "SELECT * FROM modules $where_sql ORDER BY created_at ASC $limit_sql";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$modules = $stmt->fetchAll();

$page_title = "Modules - " . $course['title'];
include '../templates/header.php';
?>

<h2 class="mb-4">📘 Modules: <?= htmlspecialchars($course['title']) ?></h2>

<form method="GET" class="mb-3">
    <input type="hidden" name="course_id" value="<?= $course_id ?>">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search modules...">
        <button class="btn btn-outline-primary">Search</button>
    </div>
</form>

<a href="<?= base_url('teacher/create_module.php?course_id=' . $course_id) ?>" class="btn btn-success mb-3">➕ Add Module</a>

<?php if (!empty($modules)): ?>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th class="w-25 text-wrap">#</th>
                <th class="w-25 text-wrap">Title</th>
                <th class="w-25 text-wrap">Created</th>
                <th style="width: 120px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules as $i => $m): ?>
                <tr>
                    <td class="text-wrap"><?= $offset + $i + 1 ?></td>
                    <td class="text-wrap"><?= htmlspecialchars($m['title']) ?></td>
                    <td class="text-wrap"><?= date('d-m-Y H:i', strtotime($m['created_at'])) ?></td>
                    <td class="text-wrap">
                        <a href="<?= base_url('teacher/edit_module.php?id=' . $m['id'] . '&course_id=' . $course_id) ?>" class="btn btn-sm btn-primary">Edit</a>
                        <a href="<?= base_url('teacher/delete_module.php?id=' . $m['id'] . '&course_id=' . $course_id) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this module?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-info">No modules found.</div>
<?php endif; ?>

<?php if ($total_pages > 1): ?>
    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?course_id=<?= $course_id ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>
