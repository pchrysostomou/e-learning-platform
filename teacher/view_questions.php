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
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    $_SESSION['error'] = "Quiz ID is required.";
    header("Location: quiz_list.php");
    exit;
}

// Verify the quiz belongs to the teacher
$quiz_check = $pdo->prepare("SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND c.teacher_id = ?");
$quiz_check->execute([$quiz_id, $teacher_id]);
$quiz = $quiz_check->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    $_SESSION['error'] = "Access denied or quiz not found.";
    header("Location: quiz_list.php");
    exit;
}

// Fetch questions for the quiz
$question_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$question_stmt->execute([$quiz_id]);
$questions = $question_stmt->fetchAll(PDO::FETCH_ASSOC);


function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

$page_title = "Questions for: " . htmlspecialchars($quiz['title']);
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">🔹 Questions for "<?= htmlspecialchars($quiz['title']) ?>"</h2>
    <p><strong>Course:</strong> <?= htmlspecialchars($quiz['course_title']) ?></p>

    <a href="add_question.php?quiz_id=<?= $quiz_id ?>" class="btn btn-success mb-3">➕ Add Another Question</a>

    <?php if (empty($questions)): ?>
        <div class="alert alert-info">No questions have been added to this quiz yet.</div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Question</th>
                    <th>Correct Answer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($questions as $index => $q): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= strtoupper($q['type']) ?></td>
                    <td class="text-wrap"><?= nl2br(htmlspecialchars($q['question_text'])) ?></td>
                    <td class="text-wrap">
                        <?php
                            if ($q['type'] === 'true_false') {
                                echo ($q['correct_answer'] === '1' || strtolower($q['correct_answer']) === 'true') ? 'True' : 'False';
                            } else {
                                echo format_json_answer_pretty($q['correct_answer'], $q['type']);
                            }
                        ?>
                    </td>
                    <td>
                        <a href="edit_question.php?id=<?= $q['id'] ?>&quiz_id=<?= $quiz_id ?>" class="btn btn-sm btn-warning">✏ Edit</a>
                        <a href="delete_question.php?id=<?= $q['id'] ?>&quiz_id=<?= $quiz_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../templates/footer.php'; ?>
