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
$course_filter = $_GET['course_id'] ?? 'all';
$quiz_filter = $_GET['quiz_id'] ?? 'all';

// Handle bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected']) && !empty($_POST['question_ids'])) {
    $ids = array_map('intval', $_POST['question_ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("DELETE FROM questions_bank WHERE id IN ($placeholders) AND teacher_id = ?");
    $stmt->execute([...$ids, $teacher_id]);
    $_SESSION['success'] = count($ids) . " question(s) deleted.";
    header("Location: question_bank.php");
    exit;
}

// Fetch filter options
$course_stmt = $pdo->prepare("SELECT id, title FROM courses WHERE teacher_id = ?");
$course_stmt->execute([$teacher_id]);
$courses = $course_stmt->fetchAll(PDO::FETCH_ASSOC);

$quiz_stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE teacher_id = ?");
$quiz_stmt->execute([$teacher_id]);
$quizzes = $quiz_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build WHERE clause based on filters
$where = "WHERE qb.teacher_id = ?";
$params = [$teacher_id];

if ($course_filter !== 'all') {
    $where .= " AND qb.course_id = ?";
    $params[] = $course_filter;
}

if ($quiz_filter !== 'all') {
    $where .= " AND qb.quiz_id = ?";
    $params[] = $quiz_filter;
}

// Fetch questions
$q_stmt = $pdo->prepare("SELECT qb.*, c.title AS course_title, q.title AS quiz_title FROM questions_bank qb LEFT JOIN courses c ON qb.course_id = c.id LEFT JOIN quizzes q ON qb.quiz_id = q.id $where ORDER BY qb.created_at DESC");
$q_stmt->execute($params);
$questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Question Bank";
require_once '../templates/header.php';
?>

<h2 class="mb-4">🔹 Question Bank</h2>

<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label">Filter by Course</label>
        <select name="course_id" class="form-select">
            <option value="all">All Courses</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $course_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Filter by Quiz</label>
        <select name="quiz_id" class="form-select">
            <option value="all">All Quizzes</option>
            <?php foreach ($quizzes as $q): ?>
                <option value="<?= $q['id'] ?>" <?= $quiz_filter == $q['id'] ? 'selected' : '' ?>><?= htmlspecialchars($q['title']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary">Apply Filter</button>
        <a href="question_bank_add_question.php" class="btn btn-success ms-3">➕ Add Question</a>
        <a href="question_bank_import.php" class="btn btn-outline-secondary ms-2">📂 Import</a>
        <a href="question_bank_download_template.php" class="btn btn-outline-secondary ms-2">📅 Template</a>
        <a href="question_bank_export.php" class="btn btn-outline-success ms-2">💾 Export</a>
    </div>
</form>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($questions)): ?>
    <div class="alert alert-info">No questions found.</div>
<?php else: ?>
    <form method="POST">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th class="w-25 text-wrap"><input type="checkbox" onclick="toggleAll(this)"></th>
                    <th class="w-25 text-wrap">Question</th>
                    <th class="w-25 text-wrap">Type</th>
                    <th class="w-25 text-wrap">Correct Answer</th>
                    <th class="w-25 text-wrap">Course</th>
                    <th class="w-25 text-wrap">Quiz</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): ?>
                    <tr>
                        <td class="text-wrap"><input type="checkbox" name="question_ids[]" value="<?= $q['id'] ?>"></td>
                        <td class="text-wrap"><?= htmlspecialchars($q['question_text']) ?></td>
                        <td class="text-wrap"><?= strtoupper($q['type']) ?></td>
                        <td class="text-wrap">
<?php
if (in_array($q['type'], ['matching', 'fill_blank_dropdown']) && is_json($q['correct_answer'])) {
    $pairs = json_decode($q['correct_answer'], true);
    echo '<ul class="mb-0 ps-3">';
    foreach ($pairs as $pair) {
        if (isset($pair['left']) && isset($pair['right'])) {
            echo '<li>' . htmlspecialchars($pair['left']) . ' → ' . htmlspecialchars($pair['right']) . '</li>';
        } else {
            echo '<li>' . htmlspecialchars((string)$pair) . '</li>';
        }
    }
    echo '</ul>';
} else {
    echo htmlspecialchars($q['correct_answer']);
}
?>
</td>

                        <td class="text-wrap"><?= htmlspecialchars($q['course_title'] ?? '-') ?></td>
                        <td class="text-wrap"><?= htmlspecialchars($q['quiz_title'] ?? '-') ?></td>
                        <td class="text-wrap">
                            <a href="question_bank_edit_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="question_bank_delete_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" name="delete_selected" class="btn btn-danger" onclick="return confirm('Delete selected questions?')">🗑️ Delete Selected</button>
    </form>
<?php endif; ?>

<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[name="question_ids[]"]');
    checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>

<?php require_once '../templates/footer.php'; ?>
