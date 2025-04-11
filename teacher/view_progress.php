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

if (!$course_id) {
    $_SESSION['error'] = "Course not specified.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Check if teacher owns the course
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->execute([$course_id, $teacher_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Access denied or course not found.";
    header("Location: " . base_url("teacher/my_courses.php"));
    exit;
}

// Get total modules
$stmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
$stmt->execute([$course_id]);
$total_modules = $stmt->fetchColumn();

// Get enrolled students and their module progress
$stmt = $pdo->prepare("
    SELECT u.id, u.name, u.email, COUNT(mp.id) AS completed_modules
    FROM enrollments e
    JOIN users u ON u.id = e.student_id
    LEFT JOIN modules m ON m.course_id = e.course_id
    LEFT JOIN module_progress mp ON mp.module_id = m.id AND mp.student_id = u.id
    WHERE e.course_id = ?
    GROUP BY u.id
    ORDER BY u.name
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();

$page_title = "Student Progress - {$course['title']}";
include '../templates/header.php';
?>

<h2 class="mb-4">📊 Student Progress - <?= htmlspecialchars($course['title']) ?></h2>

<?php if (empty($students)): ?>
    <div class="alert alert-info">No students enrolled yet.</div>
<?php else: ?>
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th class="w-25 text-wrap">👤 Student</th>
                <th class="w-25 text-wrap">📧 Email</th>
                <th class="w-25 text-wrap">📈 Progress</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
                <?php
                    $done = $s['completed_modules'];
                    $percent = ($total_modules > 0) ? round(($done / $total_modules) * 100) : 0;
                    $barColor = $percent === 100 ? 'success' : ($percent > 0 ? 'info' : 'secondary');
                ?>
                <tr>
                    <td class="text-wrap"><?= htmlspecialchars($s['name']) ?></td>
                    <td class="text-wrap"><?= htmlspecialchars($s['email']) ?></td>
                    <td class="text-wrap">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-<?= $barColor ?>" role="progressbar" style="width: <?= $percent ?>%;">
                                <?= $percent ?>%
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<a href="<?= base_url('teacher/my_courses.php') ?>" class="btn btn-outline-secondary mt-3">← Back to My Courses</a>

<?php include '../templates/footer.php'; ?>
