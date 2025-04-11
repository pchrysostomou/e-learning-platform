<?php
session_start();
require_once __DIR__ . '/header_public.php';

$page_title = "Welcome to E-Learning";
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4">📚 Welcome to Our E-Learning Platform</h1>
        <p class="lead">Empowering your learning journey in programming and computer science through interactive quizzes and structured courses.</p>
        <a href="<?= base_url('users/login.php') ?>" class="btn btn-primary btn-lg mt-3">🔐 Login Now</a>
        <a href="<?= base_url('users/register.php') ?>" class="btn btn-outline-success btn-lg mt-3 ms-2">📝 Register</a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">🧠 Interactive Quizzes</h5>
                    <p class="card-text">Test your knowledge with self-graded quizzes including multiple choice, matching, and fill-in-the-blank formats.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">💻 Programming Courses</h5>
                    <p class="card-text">From Python to C/C++ and Java – our university-level courses are structured weekly and easy to follow.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">📈 Track Your Progress</h5>
                    <p class="card-text">Students can view their scores, retake quizzes, and improve. Teachers manage quizzes and monitor performance.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
