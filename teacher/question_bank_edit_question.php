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
$question_id = $_GET['id'] ?? null;
$message = "";

if (!$question_id) {
    header("Location: question_bank.php");
    exit;
}

// Fetch question
$stmt = $pdo->prepare("SELECT * FROM questions_bank WHERE id = ? AND teacher_id = ?");
$stmt->execute([$question_id, $teacher_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found.";
    header("Location: question_bank.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $hint = $_POST['hint'] ?? null;
    $explanation = $_POST['explanation'] ?? null;
    $correct_answer = null;
    $option_a = $option_b = $option_c = $option_d = null;

    if ($type === 'matching') {
        $column_a = $_POST['column_a'] ?? [];
        $column_b = $_POST['column_b'] ?? [];
        $pairs = [];

        for ($i = 0; $i < count($column_a); $i++) {
            if (!empty($column_a[$i]) && !empty($column_b[$i])) {
                $pairs[] = ['left' => trim($column_a[$i]), 'right' => trim($column_b[$i])];
            }
        }

        $correct_answer = json_encode($pairs);
        $question_text = $_POST['question_text'];

    } elseif ($type === 'fill_blank_dropdown') {
        $fill_text = $_POST['fill_text'] ?? '';
        preg_match_all('/\[([^\[\]]+)\]/', $fill_text, $matches);
        $corrects = [];

        foreach ($matches[1] as $group) {
            $options = array_map('trim', explode('|', $group));
            if (isset($options[0])) {
                $corrects[] = $options[0];
            }
        }

        $correct_answer = json_encode($corrects);
        $question_text = $fill_text;

    } else {
        $question_text = $_POST['question_text'];
        $correct_answer = $_POST['correct_answer'];
        $option_a = $_POST['option_a'] ?? null;
        $option_b = $_POST['option_b'] ?? null;
        $option_c = $_POST['option_c'] ?? null;
        $option_d = $_POST['option_d'] ?? null;
    }

    $stmt = $pdo->prepare("UPDATE questions_bank SET type = ?, question_text = ?, correct_answer = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, hint = ?, explanation = ? WHERE id = ? AND teacher_id = ?");
    $stmt->execute([
        $type,
        $question_text,
        $correct_answer,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $hint,
        $explanation,
        $question_id,
        $teacher_id
    ]);

    $message = "✅ Question updated successfully!";
    $question = array_merge($question, [
        'type' => $type,
        'question_text' => $question_text,
        'correct_answer' => $correct_answer,
        'option_a' => $option_a,
        'option_b' => $option_b,
        'option_c' => $option_c,
        'option_d' => $option_d,
        'hint' => $hint,
        'explanation' => $explanation
    ]);
}

$page_title = "Edit Question";
require_once '../templates/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-10 py-4">
            <h2 class="mb-4">✏️ Edit Question</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Question Type</label>
                    <select name="type" class="form-select" onchange="toggleType()" required>
                        <option value="mcq" <?= $question['type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
                        <option value="true_false" <?= $question['type'] === 'true_false' ? 'selected' : '' ?>>True / False</option>
                        <option value="matching" <?= $question['type'] === 'matching' ? 'selected' : '' ?>>Matching</option>
                        <option value="fill_blank_dropdown" <?= $question['type'] === 'fill_blank_dropdown' ? 'selected' : '' ?>>Fill-in-the-Blanks</option>
                    </select>
                </div>

                <div class="mb-3" id="question-text-wrapper">
                    <label class="form-label">Question Text</label>
                    <textarea name="<?= $question['type'] === 'fill_blank_dropdown' ? 'fill_text' : 'question_text' ?>" class="form-control" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                </div>

                <div id="mcq-options" style="display: none;">
                    <input type="text" name="option_a" class="form-control mb-2" placeholder="Option A" value="<?= htmlspecialchars($question['option_a']) ?>">
                    <input type="text" name="option_b" class="form-control mb-2" placeholder="Option B" value="<?= htmlspecialchars($question['option_b']) ?>">
                    <input type="text" name="option_c" class="form-control mb-2" placeholder="Option C" value="<?= htmlspecialchars($question['option_c']) ?>">
                    <input type="text" name="option_d" class="form-control mb-2" placeholder="Option D" value="<?= htmlspecialchars($question['option_d']) ?>">
                </div>

                <div class="mb-3" id="correct-answer-wrapper">
                    <label class="form-label">Correct Answer</label>
                    <input type="text" name="correct_answer" class="form-control" value="<?= htmlspecialchars($question['correct_answer']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Hint</label>
                    <textarea name="hint" class="form-control"><?= htmlspecialchars($question['hint']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Explanation</label>
                    <textarea name="explanation" class="form-control"><?= htmlspecialchars($question['explanation']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update Question</button>
                <a href="question_bank.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
function toggleType() {
    const type = document.querySelector("select[name='type']").value;
    document.getElementById("mcq-options").style.display = (type === 'mcq' || type === 'true_false') ? 'block' : 'none';
    document.getElementById("correct-answer-wrapper").style.display = (type === 'mcq' || type === 'true_false') ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', toggleType);
</script>

<?php require_once '../templates/footer.php'; ?>
