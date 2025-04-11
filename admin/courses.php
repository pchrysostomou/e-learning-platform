<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

$params = [];
$where_sql = "";

if (!empty($search)) {
    $where_sql = "WHERE courses.title LIKE ? OR users.name LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total courses
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses LEFT JOIN users ON users.id = courses.teacher_id $where_sql");
$count_stmt->execute($params);
$total_courses = $count_stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// Fetch paginated courses (manually inject LIMIT and OFFSET as integers)
$sql = "
    SELECT courses.*, users.name AS teacher_name 
    FROM courses 
    LEFT JOIN users ON users.id = courses.teacher_id 
    $where_sql 
    ORDER BY courses.created_at DESC 
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$page_title = "Manage Courses";
include '../templates/header.php';
?>

<h2 class="mb-4">📚 Manage Courses</h2>

<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by title or teacher...">
        <button class="btn btn-outline-primary">Search</button>
    </div>
</form>

<a href="<?= base_url('admin/create_course.php') ?>" class="btn btn-success mb-3">➕ Add New Course</a>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th class="w-25 text-wrap">Title</th>
            <th class="w-25 text-wrap">Teacher</th>
            <th class="w-25 text-wrap">Created At</th>
            <th style="width: 120px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td class="text-wrap"><?= htmlspecialchars($course['title']) ?></td>
                <td class="text-wrap"><?= htmlspecialchars($course['teacher_name'] ?? '—') ?></td>
                <td class="text-wrap"><?= date('d-m-Y H:i', strtotime($course['created_at'])) ?></td>
                <td class="text-wrap">
                    <a href="<?= base_url('admin/edit_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="<?= base_url('admin/delete_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($courses) === 0): ?>
            <tr><td colspan="4" class="text-center text-muted">No courses found.</td></tr>
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
