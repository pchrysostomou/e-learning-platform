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
$preselected_course_id = $_GET['course_id'] ?? '';
$message = "";
$errors = [];

// Fetch teacher's courses
$course_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ?");
$course_stmt->execute([$teacher_id]);
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all modules belonging to teacher's courses
$module_stmt = $pdo->prepare("SELECT m.id, m.title, m.course_id, c.title AS course_title FROM modules m JOIN courses c ON m.course_id = c.id WHERE c.teacher_id = ?");
$module_stmt->execute([$teacher_id]);
$modules = $module_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $course_id = (int)$_POST['course_id'];
    $module_id = (int)$_POST['module_id'];
    $duration = (int)$_POST['duration'];

    if (empty($title) || empty($course_id) || empty($module_id) || empty($duration)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, course_id, module_id, duration_minutes, created_at) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$title, $course_id, $module_id, $duration])) {
            $quiz_id = $pdo->lastInsertId();
            header("Location: add_question.php?quiz_id=" . $quiz_id);
            exit;
        } else {
            $message = "❌ Error: Quiz could not be created.";
        }
    }
}

$page_title = "Create Quiz";
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">🔹 Create a New Quiz</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Quiz Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>

        <input type="hidden" name="course_id" value="<?= htmlspecialchars($preselected_course_id) ?>">
        <div class="mb-3">
            <label class="form-label">Course</label>
            <input type="text" class="form-control" value="<?php
                foreach ($courses as $c) {
                    if ($c['id'] == $preselected_course_id) {
                        echo htmlspecialchars($c['title']);
                        break;
                    }
                }
            ?>" readonly>
        </div>

        <div class="mb-3">
            <label for="module_id" class="form-label">Select Module (This is the Week)</label>
            <select name="module_id" id="module_id" class="form-select" required>
                <option value="">-- Select Module --</option>
                <?php foreach ($modules as $m): ?>
                    <?php if ($m['course_id'] == $preselected_course_id): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['title']) ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="duration" class="form-label">Duration (minutes)</label>
            <input type="number" name="duration" class="form-control" min="1" required>
        </div>

        <button type="submit" class="btn btn-primary">➕ Create Quiz</button>
        <a href="my_courses.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
