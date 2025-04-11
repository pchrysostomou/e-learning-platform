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
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$params = [$teacher_id];
$where_sql = "WHERE teacher_id = ?";

if (!empty($search)) {
    $where_sql .= " AND title LIKE ?";
    $params[] = "%$search%";
}

// Count total
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses $where_sql");
$count_stmt->execute($params);
$total_courses = $count_stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// Inject LIMIT and OFFSET safely
$limit_sql = "LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;
$query = "SELECT * FROM courses $where_sql ORDER BY created_at DESC $limit_sql";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$page_title = "My Courses";
include '../templates/header.php';
?>

<h2 class="mb-4">📘 My Courses</h2>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<form class="mb-3" method="GET">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by title...">
        <button class="btn btn-outline-primary">Search</button>
    </div>
</form>

<a href="<?= base_url('teacher/create_course.php') ?>" class="btn btn-success mb-3">➕ Create Course</a>

<table class="table table-bordered table-hover">
    <thead class="table-light">
        <tr>
            <th>Title</th>
            <th>Created</th>
            <th style="width: 250px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($courses as $course): ?>
            <tr>
                <td><?= htmlspecialchars($course['title']) ?></td>
                <td><?= date('Y-m-d H:i', strtotime($course['created_at'])) ?></td>
                <td>
                    <a href="<?= base_url('teacher/edit_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="<?= base_url('teacher/modules.php?course_id=' . $course['id']) ?>" class="btn btn-sm btn-secondary">Modules</a>
                    <a href="<?= base_url('teacher/create_quiz.php?course_id=' . $course['id']) ?>" class="btn btn-sm btn-info">Create Quiz</a>
                    <a href="<?= base_url('teacher/delete_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (count($courses) === 0): ?>
            <tr><td colspan="3" class="text-center text-muted">No courses found.</td></tr>
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
