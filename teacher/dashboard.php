<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] !== 'teacher') {
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

$teacher_id = $_SESSION['user_id'];
$selected_course_id = $_GET['course_id'] ?? 'all';

// Fetch teacher’s course list for dropdown
$course_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ?");
$course_stmt->execute([$teacher_id]);
$course_list = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch courses for display
$display_courses_stmt = $pdo->prepare("SELECT * FROM courses WHERE teacher_id = ?");
$display_courses_stmt->execute([$teacher_id]);
$courses = $display_courses_stmt->fetchAll();

// ✅ FIX: Use course JOIN instead of q.teacher_id
if ($selected_course_id !== 'all') {
    $quiz_stmt = $pdo->prepare("
        SELECT q.id, q.title, q.week, 
               COUNT(a.id) AS attempts, 
               COALESCE(ROUND(AVG(a.score), 2), 0) AS avg_score 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        LEFT JOIN quiz_attempts a ON a.quiz_id = q.id 
        WHERE c.teacher_id = ? AND q.course_id = ? 
        GROUP BY q.id 
        ORDER BY q.created_at
    ");
    $quiz_stmt->execute([$teacher_id, $selected_course_id]);
} else {
    $quiz_stmt = $pdo->prepare("
        SELECT q.id, q.title, q.week, 
               COUNT(a.id) AS attempts, 
               COALESCE(ROUND(AVG(a.score), 2), 0) AS avg_score 
        FROM quizzes q 
        JOIN courses c ON q.course_id = c.id 
        LEFT JOIN quiz_attempts a ON a.quiz_id = q.id 
        WHERE c.teacher_id = ? 
        GROUP BY q.id 
        ORDER BY q.created_at
    ");
    $quiz_stmt->execute([$teacher_id]);
}

$quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);

$quiz_titles = [];
$quiz_attempt_counts = [];
$quiz_avg_scores = [];
$quiz_status = ['Attempted' => 0, 'Unattempted' => 0];

foreach ($quizzes as $q) {
    $quiz_titles[] = $q['title'] . " (W" . $q['week'] . ")";
    $quiz_attempt_counts[] = (int)$q['attempts'];
    $quiz_avg_scores[] = (float)$q['avg_score'];

    if ($q['attempts'] > 0) {
        $quiz_status['Attempted']++;
    } else {
        $quiz_status['Unattempted']++;
    }
}

$page_title = "Teacher Dashboard";
require_once '../templates/header.php';
?>

<h2 class="mb-4">🎓 Teacher Dashboard</h2>

<!-- Filter by Course -->
<form method="GET" class="mb-3">
    <label for="course_id" class="form-label">📂 Filter by Course:</label>
    <div class="input-group">
        <select name="course_id" id="course_id" class="form-select">
            <option value="all" <?= $selected_course_id === 'all' ? 'selected' : '' ?>>All Courses</option>
            <?php foreach ($course_list as $course): ?>
                <option value="<?= $course['id'] ?>" <?= $selected_course_id == $course['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($course['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary">Apply</button>
    </div>
</form>

<!-- Charts -->
<div class="row mb-4">
    <div class="col-md-4">
        <canvas id="barChart"></canvas>
    </div>
    <div class="col-md-4">
        <canvas id="lineChart"></canvas>
    </div>
    <div class="col-md-4">
        <canvas id="pieChart"></canvas>
    </div>
</div>

<!-- Export Buttons -->
<?php if (count($quizzes) > 0): ?>
    <div class="mb-4">
        <a href="export_teacher_stats.php?format=pdf&course_id=<?= urlencode($selected_course_id) ?>" class="btn btn-danger me-2">📄 Export PDF</a>
        <a href="export_teacher_stats.php?format=excel&course_id=<?= urlencode($selected_course_id) ?>" class="btn btn-success">📊 Export Excel</a>
    </div>
<?php endif; ?>

<a href="<?= base_url('teacher/create_course.php') ?>" class="btn btn-success mb-3">➕ Create New Course</a>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Title</th>
            <th>Description</th>
            <th style="width: 150px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($courses)): ?>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= htmlspecialchars($course['title']) ?></td>
                    <td><?= htmlspecialchars($course['description']) ?></td>
                    <td>
                        <a href="<?= base_url('teacher/edit_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="<?= base_url('teacher/delete_course.php?id=' . $course['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3" class="text-center text-muted">No courses created yet.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const quizTitles = <?= json_encode($quiz_titles) ?>;
const attempts = <?= json_encode($quiz_attempt_counts) ?>;
const avgScores = <?= json_encode($quiz_avg_scores) ?>;
const quizStatus = <?= json_encode(array_values($quiz_status)) ?>;
const quizStatusLabels = <?= json_encode(array_keys($quiz_status)) ?>;

new Chart(document.getElementById('barChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: quizTitles,
        datasets: [{
            label: 'Attempts',
            data: attempts,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});

new Chart(document.getElementById('lineChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: quizTitles,
        datasets: [{
            label: 'Average Score',
            data: avgScores,
            fill: false,
            borderColor: 'rgba(255, 99, 132, 1)',
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});

new Chart(document.getElementById('pieChart').getContext('2d'), {
    type: 'pie',
    data: {
        labels: quizStatusLabels,
        datasets: [{
            data: quizStatus,
            backgroundColor: ['rgba(75, 192, 192, 0.7)', 'rgba(255, 99, 132, 0.7)'],
            borderColor: ['rgba(75, 192, 192, 1)', 'rgba(255, 99, 132, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php require_once '../templates/footer.php'; ?>
