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
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    $_SESSION['error'] = "Quiz ID is missing.";
    header("Location: available_quizzes.php");
    exit;
}

// Check if already attempted
$attempt_check = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ? AND quiz_id = ?");
$attempt_check->execute([$student_id, $quiz_id]);
if ($attempt_check->fetchColumn() > 0) {
    $_SESSION['info'] = "You have already taken this quiz.";
    header("Location: view_score.php?quiz_id=$quiz_id");
    exit;
}

// Fetch quiz info
$quiz_stmt = $pdo->prepare("SELECT q.*, c.title AS course_title FROM quizzes q JOIN courses c ON c.id = q.course_id WHERE q.id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch questions
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$q_stmt->execute([$quiz_id]);
$questions = $q_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Take Quiz";
require_once '../templates/header.php';
?>

<div class="container py-4">
    <h2 class="mb-3">🧠 <?= htmlspecialchars($quiz['title']) ?> (<?= htmlspecialchars($quiz['course_title']) ?>)</h2>

    <form method="POST" action="submit_quiz.php">
        <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">

        <?php if (empty($questions)): ?>
            <div class="alert alert-warning">No questions found in this quiz.</div>
        <?php else: ?>
            <?php foreach ($questions as $index => $q): ?>
                <div class="mb-4 border p-3 rounded">
                    <strong>Q<?= $index + 1 ?>:</strong><br>

                    <?php if ($q['type'] === 'fill_blank_dropdown'): ?>
                        <?php
                        $rendered = preg_replace_callback('/\[([^\[\]]+)\]/', function ($match) use ($q) {
                            $options = explode('|', $match[1]);
                            $select = '<select name="answers[' . $q['id'] . '][]" class="form-select d-inline w-auto mx-1" required>';
                            foreach ($options as $opt) {
                                $opt = htmlspecialchars(trim($opt));
                                $select .= "<option value=\"$opt\">$opt</option>";
                            }
                            $select .= '</select>';
                            return $select;
                        }, $q['question_text']);
                        echo "<div>$rendered</div>";
                        ?>

                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($q['question_text'])) ?><br><br>

                        <?php if ($q['type'] === 'mcq'): ?>
                            <?php foreach (['option_a', 'option_b', 'option_c', 'option_d'] as $opt): ?>
                                <?php if (!empty($q[$opt])): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="<?= htmlspecialchars($q[$opt]) ?>" required>
                                        <label class="form-check-label"><?= htmlspecialchars($q[$opt]) ?></label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                        <?php elseif ($q['type'] === 'true_false'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="True" required>
                                <label class="form-check-label">True</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answers[<?= $q['id'] ?>]" value="False" required>
                                <label class="form-check-label">False</label>
                            </div>

                        <?php elseif ($q['type'] === 'matching'): ?>
                            <?php
                            $pairs = json_decode($q['correct_answer'], true);
                            if (is_array($pairs)):
                                $rightOptions = array_column($pairs, 'right');
                                shuffle($rightOptions);
                                foreach ($pairs as $i => $pair): ?>
                                    <div class="row mb-2">
                                        <label class="col-md-4"><?= htmlspecialchars($pair['left']) ?> ↔</label>
                                        <div class="col-md-8">
                                            <select name="answers[<?= $q['id'] ?>][<?= $i ?>]" class="form-select" required>
                                                <option value="">-- Select --</option>
                                                <?php foreach ($rightOptions as $opt): ?>
                                                    <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-danger">Matching data invalid</div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success">Submit Quiz</button>
        <?php endif; ?>
    </form>
</div>

<?php require_once '../templates/footer.php'; ?>
