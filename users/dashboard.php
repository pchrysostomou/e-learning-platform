<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'student') {
    header("Location: " . base_url("index.php"));
    exit;
}

$student_id = $_SESSION['user_id'];
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 5;
$offset = ($page - 1) * $per_page;

$params = [];
$where_sql = "WHERE 1";

if (!empty($search)) {
    $where_sql .= " AND (c.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Total course count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM courses c LEFT JOIN users u ON u.id = c.teacher_id $where_sql");
$count_stmt->execute($params);
$total_courses = $count_stmt->fetchColumn();
$total_pages = ceil($total_courses / $per_page);

// Fetch paginated courses
$limit_sql = "LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

$stmt = $pdo->prepare("
    SELECT c.*, u.name AS teacher_name 
    FROM courses c 
    LEFT JOIN users u ON u.id = c.teacher_id 
    $where_sql 
    ORDER BY c.created_at DESC 
    $limit_sql
");
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Fetch student's enrolled course IDs
$enroll_stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enroll_stmt->execute([$student_id]);
$enrolled_ids = $enroll_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = "All Courses";
include '../templates/header.php';
?>

<h2 class="mb-4">📖 All Courses</h2>

<form method="GET" class="mb-3">
    <div class="input-group">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search by title or teacher...">
        <button class="btn btn-outline-primary">Search</button>
    </div>
</form>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($courses)): ?>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>Title</th>
                <th>Teacher</th>
                <th>Created</th>
                <th style="width: 180px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= htmlspecialchars($course['title']) ?></td>
                    <td><?= htmlspecialchars($course['teacher_name'] ?? '—') ?></td>
                    <td><?= date('Y-m-d', strtotime($course['created_at'])) ?></td>
                    <td>
                        <a href="<?= base_url('users/course_modules.php?course_id=' . $course['id']) ?>" class="btn btn-sm btn-primary">View Modules</a>
                        <?php if (!in_array($course['id'], $enrolled_ids)): ?>
                            <a href="<?= base_url('users/enroll.php?course_id=' . $course['id']) ?>" class="btn btn-sm btn-success">Enroll</a>
                        <?php else: ?>
                            <span class="badge bg-secondary">Enrolled</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-info">No courses found.</div>
<?php endif; ?>

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
