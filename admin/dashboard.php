<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();
redirect_if_not_admin();

$page_title = "Admin Dashboard";

// General Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// Courses per teacher
$teacherCourseStmt = $pdo->query("SELECT u.name, COUNT(c.id) AS course_count 
    FROM users u 
    LEFT JOIN courses c ON u.id = c.teacher_id 
    WHERE u.role = 'teacher' 
    GROUP BY u.id 
    ORDER BY course_count DESC 
    LIMIT 5");
$teacherCourses = $teacherCourseStmt->fetchAll(PDO::FETCH_ASSOC);

// User registrations over time (last 6 months)
$months = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month, COUNT(*) AS total
    FROM users
    GROUP BY month
    ORDER BY MIN(created_at) DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);
$userRegistrations = array_reverse($months);

// Quiz creation over time (last 6 months)
$quizGrowth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month, COUNT(*) AS total
    FROM quizzes
    GROUP BY month
    ORDER BY MIN(created_at) DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_KEY_PAIR);
$quizCreation = array_reverse($quizGrowth);

// Top 5 quizzes by question count
$topQuizQuestions = $pdo->query("
    SELECT q.title, COUNT(qq.id) as count
    FROM quizzes q
    JOIN questions qq ON q.id = qq.quiz_id
    GROUP BY q.id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top 5 courses by quiz count
$topCourseQuizzes = $pdo->query("
    SELECT c.title, COUNT(q.id) as count
    FROM courses c
    JOIN quizzes q ON q.course_id = c.id
    GROUP BY c.id
    ORDER BY count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../templates/header.php';
?>

<h2 class="mb-4">📊 Admin Dashboard</h2>

<!-- Stats Summary -->
<div class="row">
    <?php
    $stats = [
        ['Users', $totalUsers, 'primary'],
        ['Courses', $totalCourses, 'success'],
        ['Teachers', $totalTeachers, 'info'],
        ['Students', $totalStudents, 'warning'],
    ];
    foreach ($stats as [$label, $value, $color]): ?>
    <div class="col-md-3">
        <div class="card text-bg-<?= $color ?> mb-3">
            <div class="card-body">
                <h5 class="card-title"><?= $label ?></h5>
                <p class="card-text fs-4"><?= $value ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row mt-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">📌 User Roles</div>
            <div class="card-body"><canvas id="userRoleChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">👩‍🏫 Top Teachers by Courses</div>
            <div class="card-body"><canvas id="teacherCoursesChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">📈 User Registrations</div>
            <div class="card-body"><canvas id="userGrowthChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">📊 Quizzes Created Over Time</div>
            <div class="card-body"><canvas id="quizGrowthChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">📝 Questions per Quiz</div>
            <div class="card-body"><canvas id="quizQuestionChart"></canvas></div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">📚 Quizzes per Course</div>
            <div class="card-body"><canvas id="courseQuizChart"></canvas></div>
        </div>
    </div>
</div>

<a href="users.php" class="btn btn-outline-primary mt-3">Manage Users</a>
<a href="courses.php" class="btn btn-outline-success mt-3">Manage Courses</a>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const pieChart = new Chart(document.getElementById('userRoleChart'), {
    type: 'pie',
    data: {
        labels: ['Admin', 'Teacher', 'Student'],
        datasets: [{
            data: [1, <?= $totalTeachers ?>, <?= $totalStudents ?>],
            backgroundColor: ['#007bff', '#17a2b8', '#ffc107']
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('teacherCoursesChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($teacherCourses, 'name')) ?>,
        datasets: [{
            label: 'Courses',
            data: <?= json_encode(array_column($teacherCourses, 'course_count')) ?>,
            backgroundColor: '#28a745'
        }]
    },
    options: { plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($userRegistrations)) ?>,
        datasets: [{
            label: 'Users',
            data: <?= json_encode(array_values($userRegistrations)) ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.1)',
            fill: true,
            tension: 0.3
        }]
    }
});

new Chart(document.getElementById('quizGrowthChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($quizCreation)) ?>,
        datasets: [{
            label: 'Quizzes',
            data: <?= json_encode(array_values($quizCreation)) ?>,
            borderColor: '#fd7e14',
            backgroundColor: 'rgba(253,126,20,0.1)',
            fill: true,
            tension: 0.3
        }]
    }
});

new Chart(document.getElementById('quizQuestionChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($topQuizQuestions)) ?>,
        datasets: [{
            label: 'Questions',
            data: <?= json_encode(array_values($topQuizQuestions)) ?>,
            backgroundColor: '#6f42c1'
        }]
    }
});

new Chart(document.getElementById('courseQuizChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($topCourseQuizzes)) ?>,
        datasets: [{
            label: 'Quizzes',
            data: <?= json_encode(array_values($topCourseQuizzes)) ?>,
            backgroundColor: '#20c997'
        }]
    }
});
</script>

<?php require_once '../templates/footer.php'; ?>
