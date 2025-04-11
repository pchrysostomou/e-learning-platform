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

$question_id = $_GET['id'] ?? null;

if (!$question_id) {
    $_SESSION['error'] = "Question ID is required.";
    header("Location: question_bank.php");
    exit;
}

// Fetch question
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found.";
    header("Location: question_bank.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = $_POST['question'] ?? '';
    $type = $_POST['type'];
    $correct_answer = null;
    $hint = $_POST['hint'] ?? null;
    $explanation = $_POST['explanation'] ?? null;
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
    } elseif ($type === 'fill_blank_dropdown') {
        preg_match_all('/\[([^\[\]]+)\]/', $question_text, $matches);
        $corrects = [];

        foreach ($matches[1] as $group) {
            $options = array_map('trim', explode('|', $group));
            if (isset($options[0])) {
                $corrects[] = $options[0];
            }
        }

        $correct_answer = json_encode($corrects);
    } elseif ($type === 'true_false') {
        $correct_answer = $_POST['correct_answer'];
    } else {
        $correct_answer = $_POST['correct_answer'];
        $option_a = $_POST['option_a'] ?? null;
        $option_b = $_POST['option_b'] ?? null;
        $option_c = $_POST['option_c'] ?? null;
        $option_d = $_POST['option_d'] ?? null;
    }

    $update = $pdo->prepare("UPDATE questions SET 
        question_text = ?, 
        type = ?, 
        correct_answer = ?, 
        option_a = ?, 
        option_b = ?, 
        option_c = ?, 
        option_d = ?, 
        hint = ?, 
        explanation = ? 
        WHERE id = ?");

    $update->execute([
        $question_text,
        $type,
        $correct_answer,
        $option_a,
        $option_b,
        $option_c,
        $option_d,
        $hint,
        $explanation,
        $question_id
    ]);

    $_SESSION['success'] = "✅ Question updated successfully.";
    header("Location: view_questions.php?quiz_id=" . $question['quiz_id']);
    exit;
}

$page_title = "Edit Question";
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="container py-4">
    <h2 class="mb-4">✏️ Edit Question</h2>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Question Type</label>
            <select name="type" class="form-select" onchange="toggleType(this.value)" required>
                <option value="mcq" <?= $question['type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
                <option value="true_false" <?= $question['type'] === 'true_false' ? 'selected' : '' ?>>True / False</option>
                <option value="matching" <?= $question['type'] === 'matching' ? 'selected' : '' ?>>Matching (A ↔ B)</option>
                <option value="fill_blank_dropdown" <?= $question['type'] === 'fill_blank_dropdown' ? 'selected' : '' ?>>Fill-in-the-Blank Dropdown</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Question Text</label>
            <textarea name="question" class="form-control" required><?= htmlspecialchars($question['question_text']) ?></textarea>
        </div>

        <div id="mcq-options" style="display: <?= $question['type'] === 'mcq' ? 'block' : 'none' ?>;">
            <input type="text" name="option_a" class="form-control mb-2" placeholder="Option A" value="<?= htmlspecialchars($question['option_a']) ?>">
            <input type="text" name="option_b" class="form-control mb-2" placeholder="Option B" value="<?= htmlspecialchars($question['option_b']) ?>">
            <input type="text" name="option_c" class="form-control mb-2" placeholder="Option C" value="<?= htmlspecialchars($question['option_c']) ?>">
            <input type="text" name="option_d" class="form-control mb-2" placeholder="Option D" value="<?= htmlspecialchars($question['option_d']) ?>">
        </div>

        <div id="true-false-options" style="display: <?= $question['type'] === 'true_false' ? 'block' : 'none' ?>;">
            <label class="form-label">Correct Answer</label>
            <select name="correct_answer" class="form-select">
                <option value="True" <?= $question['correct_answer'] === 'True' ? 'selected' : '' ?>>True</option>
                <option value="False" <?= $question['correct_answer'] === 'False' ? 'selected' : '' ?>>False</option>
            </select>
        </div>

        <div id="matching-options" style="display: <?= $question['type'] === 'matching' ? 'block' : 'none' ?>;">
            <label class="form-label">Matching Pairs</label>
            <div id="pair-list">
                <?php
                $pairs = json_decode($question['correct_answer'], true);
                if (is_array($pairs)) {
                    foreach ($pairs as $pair) {
                        echo '<div class="row mb-2">
                            <div class="col"><input name="column_a[]" class="form-control" value="' . htmlspecialchars($pair['left']) . '"></div>
                            <div class="col"><input name="column_b[]" class="form-control" value="' . htmlspecialchars($pair['right']) . '"></div>
                        </div>';
                    }
                }
                ?>
            </div>
            <button type="button" class="btn btn-outline-secondary" onclick="addMatchPair()">➕ Add Pair</button>
        </div>

        <div class="mb-3">
            <label class="form-label">Hint (optional)</label>
            <textarea name="hint" class="form-control"><?= htmlspecialchars($question['hint']) ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Explanation (optional)</label>
            <textarea name="explanation" class="form-control"><?= htmlspecialchars($question['explanation']) ?></textarea>
        </div>

        <?php if (!in_array($question['type'], ['true_false', 'matching', 'fill_blank_dropdown'])): ?>
        <div class="mb-3">
            <label class="form-label">Correct Answer</label>
            <input type="text" name="correct_answer" class="form-control" value="<?= htmlspecialchars($question['correct_answer']) ?>">
        </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
        <a href="view_questions.php?quiz_id=<?= $question['quiz_id'] ?>" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
function toggleType(type) {
    document.getElementById("mcq-options").style.display = (type === "mcq") ? "block" : "none";
    document.getElementById("true-false-options").style.display = (type === "true_false") ? "block" : "none";
    document.getElementById("matching-options").style.display = (type === "matching") ? "block" : "none";
}

function addMatchPair() {
    const pairList = document.getElementById("pair-list");
    const div = document.createElement("div");
    div.classList.add("row", "mb-2");
    div.innerHTML = `
        <div class="col"><input name="column_a[]" class="form-control"></div>
        <div class="col"><input name="column_b[]" class="form-control"></div>
    `;
    pairList.appendChild(div);
}
</script>

<?php require_once '../templates/footer.php'; ?>
