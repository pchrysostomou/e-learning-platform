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

// Fetch quiz stats
$recent_stmt = $pdo->prepare("
    SELECT qa.id AS attempt_id, q.title AS quiz_title, qa.score, qa.total_questions, qa.attempted_at
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.user_id = ?
    ORDER BY qa.attempted_at DESC
    LIMIT 5
");
$recent_stmt->execute([$student_id]);
$recent_attempts = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stats_stmt = $pdo->prepare("
    SELECT COUNT(*) AS total_attempts, 
           COALESCE(ROUND(AVG(score), 2), 0) AS avg_score
    FROM quiz_attempts
    WHERE user_id = ?
");
$stats_stmt->execute([$student_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Chart data
$chart_labels = [];
$chart_scores = [];

foreach (array_reverse($recent_attempts) as $row) {
    $chart_labels[] = date("M j", strtotime($row['attempted_at']));
    $chart_scores[] = $row['score'];
}

// Score ranges for pie chart
$range_counts = [
    'Poor (0–49)' => 0,
    'Average (50–74)' => 0,
    'Good (75–89)' => 0,
    'Excellent (90–100)' => 0,
];

foreach ($chart_scores as $score) {
    if ($score < 50) $range_counts['Poor (0–49)']++;
    elseif ($score < 75) $range_counts['Average (50–74)']++;
    elseif ($score < 90) $range_counts['Good (75–89)']++;
    else $range_counts['Excellent (90–100)']++;
}

// Course search logic
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

// Fetch enrolled course IDs
$enroll_stmt = $pdo->prepare("SELECT course_id FROM enrollments WHERE student_id = ?");
$enroll_stmt->execute([$student_id]);
$enrolled_ids = $enroll_stmt->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Student Dashboard";
include '../templates/header.php';
?>

<h2 class="mb-4">🎓 Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>

<!-- Quiz Stats -->
<div class="mb-5">
    <h4>📊 Your Quiz Progress</h4>
    <ul class="list-group mb-3">
        <li class="list-group-item"><strong>Total Quizzes Taken:</strong> <?= $stats['total_attempts'] ?></li>
        <li class="list-group-item"><strong>Average Score:</strong> <?= $stats['avg_score'] ?> points</li>
    </ul>

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

    <h5>📝 Recent Quizzes</h5>
    <?php if (count($recent_attempts) === 0): ?>
        <div class="alert alert-warning">You haven't taken any quizzes yet.</div>
    <?php else: ?>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Quiz</th>
                    <th>Score</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_attempts as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['quiz_title']) ?></td>
                        <td><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                        <td><?= date("Y-m-d H:i", strtotime($row['attempted_at'])) ?></td>
                        <td><a href="quiz_results.php?attempt_id=<?= $row['attempt_id'] ?>" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Course List -->
<h4 class="mb-3">📚 All Courses</h4>

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

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($chart_labels) ?>;
const scores = <?= json_encode($chart_scores) ?>;

const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Scores',
            data: scores,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});

const lineCtx = document.getElementById('lineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Quiz Score Trend',
            data: scores,
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

const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_keys($range_counts)) ?>,
        datasets: [{
            label: 'Score Ranges',
            data: <?= json_encode(array_values($range_counts)) ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(54, 162, 235, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(54, 162, 235, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include '../templates/footer.php'; ?>
