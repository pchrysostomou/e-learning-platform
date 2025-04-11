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
$quiz_id = $_GET['quiz_id'] ?? null;
$message = "";

if (!$question_id || !$quiz_id) {
    $_SESSION['error'] = "Missing question or quiz ID.";
    header("Location: quiz_list.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND quiz_id = ?");
$stmt->execute([$question_id, $quiz_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found.";
    header("Location: quiz_list.php");
    exit;
}

function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $hint = $_POST['hint'] ?? null;
    $explanation = $_POST['explanation'] ?? null;
    $correct_answer = null;

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
        $question_text = $_POST['question'];
        $option_a = $option_b = $option_c = $option_d = null;

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
        $option_a = $option_b = $option_c = $option_d = null;

    } elseif ($type === 'true_false') {
        $question_text = $_POST['question'];
        $correct_answer = $_POST['true_false_answer'] ?? null;
        $option_a = $option_b = $option_c = $option_d = null;

    } else {
        $question_text = $_POST['question'];
        $correct_answer = $_POST['correct_answer'] ?? null;
        $option_a = $_POST['option_a'] ?? null;
        $option_b = $_POST['option_b'] ?? null;
        $option_c = $_POST['option_c'] ?? null;
        $option_d = $_POST['option_d'] ?? null;
    }

    $update = $pdo->prepare("UPDATE questions SET 
        question_text = ?, type = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, 
        correct_answer = ?, hint = ?, explanation = ? 
        WHERE id = ? AND quiz_id = ?");
    $update->execute([
        $question_text, $type,
        $option_a, $option_b, $option_c, $option_d,
        $correct_answer, $hint, $explanation,
        $question_id, $quiz_id
    ]);

    $message = "✅ Question updated successfully!";
}
?>

<?php require_once '../templates/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-10 py-4">
            <h2 class="mb-4">✏ Edit Question</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Question Type</label>
                    <select name="type" class="form-select" onchange="toggleQuestionType()" id="questionTypeSelect" required>
                        <option value="mcq" <?= $question['type'] === 'mcq' ? 'selected' : '' ?>>Multiple Choice</option>
                        <option value="true_false" <?= $question['type'] === 'true_false' ? 'selected' : '' ?>>True / False</option>
                        <option value="matching" <?= $question['type'] === 'matching' ? 'selected' : '' ?>>Matching (Column A ↔ B)</option>
                        <option value="fill_blank_dropdown" <?= $question['type'] === 'fill_blank_dropdown' ? 'selected' : '' ?>>Fill in the Blanks (Dropdown)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <textarea name="<?= $question['type'] === 'fill_blank_dropdown' ? 'fill_text' : 'question' ?>" class="form-control" id="questionField" required><?= htmlspecialchars($question['question_text']) ?></textarea>
                </div>

                <div id="mcq-options">
                    <input type="text" name="option_a" value="<?= htmlspecialchars($question['option_a']) ?>" class="form-control mb-2" placeholder="Option A">
                    <input type="text" name="option_b" value="<?= htmlspecialchars($question['option_b']) ?>" class="form-control mb-2" placeholder="Option B">
                    <input type="text" name="option_c" value="<?= htmlspecialchars($question['option_c']) ?>" class="form-control mb-2" placeholder="Option C">
                    <input type="text" name="option_d" value="<?= htmlspecialchars($question['option_d']) ?>" class="form-control mb-2" placeholder="Option D">
                </div>

                <div class="mb-3" id="trueFalseOptions" style="display: none;">
                    <label class="form-label">Correct Answer</label>
                    <select name="true_false_answer" class="form-select">
                        <option value="True" <?= strtolower($question['correct_answer']) === 'true' ? 'selected' : '' ?>>True</option>
                        <option value="False" <?= strtolower($question['correct_answer']) === 'false' ? 'selected' : '' ?>>False</option>
                    </select>
                </div>

                <div id="matching-options" style="display: none;">
                    <label class="form-label">Matching Pairs</label>
                    <div id="pair-list">
                        <?php if ($question['type'] === 'matching' && is_json($question['correct_answer'])):
                            $pairs = json_decode($question['correct_answer'], true);
                            foreach ($pairs as $p): ?>
                                <div class="row mb-2">
                                    <div class="col-md-5"><input type="text" name="column_a[]" class="form-control" value="<?= htmlspecialchars($p['left']) ?>"></div>
                                    <div class="col-md-5"><input type="text" name="column_b[]" class="form-control" value="<?= htmlspecialchars($p['right']) ?>"></div>
                                    <div class="col-md-2 text-end"><button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.row').remove()">✖</button></div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addMatchPair()">➕ Add Pair</button>
                    <div class="mt-3">
                        <label class="form-label">JSON Preview</label>
                        <pre id="jsonPreview" class="border p-2 bg-light"><?= htmlspecialchars($question['correct_answer']) ?></pre>
                    </div>
                </div>

                <div id="fill-blank-options" style="display: none;">
                    <label class="form-label">Preview</label>
                    <div id="fillPreview" class="border p-2 bg-light"></div>
                </div>

                <div class="mb-3 mt-3" id="correctAnswerWrapper">
                    <label class="form-label">Correct Answer</label>
                    <input type="text" name="correct_answer" value="<?= htmlspecialchars($question['correct_answer']) ?>" class="form-control" id="correctAnswerInput">
                </div>

                <div class="mb-3">
                    <label class="form-label">Hint</label>
                    <textarea name="hint" class="form-control"><?= htmlspecialchars($question['hint']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Explanation</label>
                    <textarea name="explanation" class="form-control"><?= htmlspecialchars($question['explanation']) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <a href="view_questions.php?quiz_id=<?= $quiz_id ?>" class="btn btn-secondary">🔙 Back</a>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuestionType() {
    const type = document.getElementById("questionTypeSelect").value;
    document.getElementById("mcq-options").style.display = (type === 'mcq') ? 'block' : 'none';
    document.getElementById("trueFalseOptions").style.display = (type === 'true_false') ? 'block' : 'none';
    document.getElementById("matching-options").style.display = (type === 'matching') ? 'block' : 'none';
    document.getElementById("fill-blank-options").style.display = (type === 'fill_blank_dropdown') ? 'block' : 'none';
    document.getElementById("correctAnswerWrapper").style.display = (type === 'mcq') ? 'block' : 'none';

    updatePreview?.();
    updateFillPreview?.();
}

function addMatchPair() {
    const container = document.getElementById("pair-list");
    const row = document.createElement("div");
    row.className = "row mb-2";
    row.innerHTML = `
        <div class="col-md-5"><input type="text" name="column_a[]" class="form-control" placeholder="Column A" oninput="updatePreview()"></div>
        <div class="col-md-5"><input type="text" name="column_b[]" class="form-control" placeholder="Column B" oninput="updatePreview()"></div>
        <div class="col-md-2 text-end"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.row').remove(); updatePreview();">✖</button></div>
    `;
    container.appendChild(row);
    updatePreview();
}

function updatePreview() {
    const aInputs = document.querySelectorAll("input[name='column_a[]']");
    const bInputs = document.querySelectorAll("input[name='column_b[]']");
    let result = [];

    for (let i = 0; i < aInputs.length; i++) {
        const left = aInputs[i].value.trim();
        const right = bInputs[i].value.trim();
        if (left && right) {
            result.push({ left, right });
        }
    }

    document.getElementById("jsonPreview").textContent = JSON.stringify(result, null, 2);
}

function updateFillPreview() {
    const input = document.getElementById("questionField").value;
    let rendered = input.replace(/\[([^\[\]]+)\]/g, (match, options) => {
        const opts = options.split('|').map(opt => `<option>${opt.trim()}</option>`).join('');
        return `<select class='form-select d-inline w-auto mx-1'>${opts}</select>`;
    });
    document.getElementById("fillPreview").innerHTML = rendered;
}

document.addEventListener('DOMContentLoaded', () => {
    toggleQuestionType();
    updatePreview();
    updateFillPreview();
});
</script>

<?php require_once '../templates/footer.php'; ?>
