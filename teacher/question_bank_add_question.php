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
$errors = [];

// Fetch teacher's courses and quizzes for dropdowns
$course_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ?");
$course_stmt->execute([$teacher_id]);
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

$quiz_stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE teacher_id = ?");
$quiz_stmt->execute([$teacher_id]);
$quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text']);
    $type = $_POST['type'] ?? 'mcq';
    $course_id = $_POST['course_id'] ?? null;
    $quiz_id = $_POST['quiz_id'] ?? null;
    $correct_answer = trim($_POST['correct_answer']);
    $hint = trim($_POST['hint'] ?? '');
    $explanation = trim($_POST['explanation'] ?? '');

    $option_a = $_POST['option_a'] ?? null;
    $option_b = $_POST['option_b'] ?? null;
    $option_c = $_POST['option_c'] ?? null;
    $option_d = $_POST['option_d'] ?? null;

    if (empty($question_text) || empty($correct_answer)) {
        $errors[] = "Question text and correct answer are required.";
    }

    if ($type === 'mcq' && (empty($option_a) || empty($option_b))) {
        $errors[] = "MCQ questions require at least options A and B.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO questions_bank (teacher_id, course_id, quiz_id, question_text, type, option_a, option_b, option_c, option_d, correct_answer, hint, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $teacher_id,
            $course_id ?: null,
            $quiz_id ?: null,
            $question_text,
            $type,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_answer,
            $hint,
            $explanation
        ]);

        $_SESSION['success'] = "✅ Question added successfully.";
        header("Location: question_bank.php");
        exit;
    }
}

$page_title = "Add Question to Bank";
require_once '../templates/header.php';
?>

<h2 class="mb-4">➕ Add Question to Question Bank</h2>

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
        <label class="form-label">Question Text</label>
        <textarea name="question_text" class="form-control" required><?= htmlspecialchars($_POST['question_text'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" id="questionType" onchange="toggleOptions()">
            <option value="mcq" <?= ($_POST['type'] ?? '') === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
            <option value="true_false" <?= ($_POST['type'] ?? '') === 'true_false' ? 'selected' : '' ?>>True/False</option>
        </select>
    </div>

    <div id="mcqOptions">
        <div class="mb-2"><input type="text" name="option_a" class="form-control" placeholder="Option A" value="<?= htmlspecialchars($_POST['option_a'] ?? '') ?>"></div>
        <div class="mb-2"><input type="text" name="option_b" class="form-control" placeholder="Option B" value="<?= htmlspecialchars($_POST['option_b'] ?? '') ?>"></div>
        <div class="mb-2"><input type="text" name="option_c" class="form-control" placeholder="Option C (optional)" value="<?= htmlspecialchars($_POST['option_c'] ?? '') ?>"></div>
        <div class="mb-2"><input type="text" name="option_d" class="form-control" placeholder="Option D (optional)" value="<?= htmlspecialchars($_POST['option_d'] ?? '') ?>"></div>
    </div>

    <div class="mb-3">
        <label class="form-label">Correct Answer</label>
        <input type="text" name="correct_answer" class="form-control" required value="<?= htmlspecialchars($_POST['correct_answer'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Hint (optional)</label>
        <textarea name="hint" class="form-control"><?= htmlspecialchars($_POST['hint'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Explanation (optional)</label>
        <textarea name="explanation" class="form-control"><?= htmlspecialchars($_POST['explanation'] ?? '') ?></textarea>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Course (optional)</label>
            <select name="course_id" class="form-select">
                <option value="">-- None --</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($_POST['course_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Quiz (optional)</label>
            <select name="quiz_id" class="form-select">
                <option value="">-- None --</option>
                <?php foreach ($quizzes as $q): ?>
                    <option value="<?= $q['id'] ?>" <?= ($_POST['quiz_id'] ?? '') == $q['id'] ? 'selected' : '' ?>><?= htmlspecialchars($q['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Question</button>
    <a href="question_bank.php" class="btn btn-secondary">Cancel</a>
</form>

<script>
function toggleOptions() {
    const type = document.getElementById('questionType').value;
    const mcqBlock = document.getElementById('mcqOptions');
    mcqBlock.style.display = (type === 'mcq') ? 'block' : 'none';
}
toggleOptions();
</script>

<?php require_once '../templates/footer.php'; ?>
