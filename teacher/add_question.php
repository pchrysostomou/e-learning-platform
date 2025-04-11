<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

redirect_if_not_logged_in();

if ($_SESSION['user_role'] === 'student') {
    header("Location: " . base_url("index.php"));
    exit;
}

$quiz_id_from_url = $_GET['quiz_id'] ?? null;
$message = "";

// Fetch teacher's quizzes
$teacher_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT q.id, q.title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE c.teacher_id = ? ORDER BY q.created_at DESC");
$stmt->execute([$teacher_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = $_POST['quiz_id'];
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
        $question = $_POST['question'];
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
        $question = $fill_text;
        $option_a = $option_b = $option_c = $option_d = null;

    } elseif ($type === 'true_false') {
        $question = $_POST['question'];
        $correct_answer = $_POST['true_false_answer'] ?? null; // ✅ fixed
        $option_a = $option_b = $option_c = $option_d = null;

    } else {
        $question = $_POST['question'];
        $correct_answer = $_POST['correct_answer'] ?? null;
        $option_a = $_POST['option_a'] ?? null;
        $option_b = $_POST['option_b'] ?? null;
        $option_c = $_POST['option_c'] ?? null;
        $option_d = $_POST['option_d'] ?? null;
    }

    $stmt = $pdo->prepare("INSERT INTO questions 
        (quiz_id, question_text, type, option_a, option_b, option_c, option_d, correct_answer, hint, explanation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $quiz_id, $question, $type,
        $option_a, $option_b, $option_c, $option_d,
        $correct_answer, $hint, $explanation
    ]);

    $message = "✅ Question added successfully!";
}

$page_title = "Add Question";
require_once '../templates/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 bg-light">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-10 py-4">
            <h2 class="mb-4">➕ Add Question</h2>

            <?php if ($quiz_id_from_url): ?>
                <a href="question_bank_select.php?quiz_id=<?= urlencode($quiz_id_from_url) ?>" class="btn btn-outline-secondary float-end mb-3">
                    📋 Use from Question Bank
                </a>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Select Quiz</label>
                    <select name="quiz_id" class="form-select" required>
                        <option value="">-- Select a Quiz --</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option value="<?= $quiz['id'] ?>" <?= ($quiz_id_from_url == $quiz['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($quiz['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Question Type</label>
                    <select name="type" class="form-select" onchange="toggleQuestionType()" required>
                        <option value="mcq">Multiple Choice</option>
                        <option value="true_false">True / False</option>
                        <option value="matching">Matching (Column A ↔ B)</option>
                        <option value="fill_blank_dropdown">Fill in the Blanks (Dropdown)</option>
                    </select>
                </div>

                <div class="mb-3" id="question-text-wrapper">
                    <label class="form-label">Question Text</label>
                    <textarea name="question" class="form-control" id="questionField" required></textarea>
                </div>

                <!-- MCQ Options -->
                <div id="mcq-options">
                    <input type="text" name="option_a" class="form-control mb-2" placeholder="Option A">
                    <input type="text" name="option_b" class="form-control mb-2" placeholder="Option B">
                    <input type="text" name="option_c" class="form-control mb-2" placeholder="Option C">
                    <input type="text" name="option_d" class="form-control mb-2" placeholder="Option D">
                </div>

                <!-- True/False Dropdown -->
                <div class="mb-3" id="trueFalseOptions" style="display: none;">
                    <label class="form-label">Select Correct Answer</label>
                    <select name="true_false_answer" class="form-select">
                        <option value="True">True</option>
                        <option value="False">False</option>
                    </select>
                </div>

                <!-- Matching -->
                <div id="matching-options" style="display: none;">
                    <label class="form-label">Matching Pairs (Column A ↔ B)</label>
                    <div id="pair-list"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addMatchPair()">➕ Add Pair</button>
                    <div class="mt-3">
                        <label class="form-label">JSON Preview</label>
                        <pre id="jsonPreview" class="border p-2 bg-light">[]</pre>
                    </div>
                </div>

                <!-- Fill-in-the-Blank -->
                <div id="fill-blank-options" style="display: none;">
                    <label class="form-label">Fill-in Sentence</label>
                    <textarea name="fill_text" id="fillTextInput" class="form-control" oninput="updateFillPreview()" placeholder="e.g. The capital of France is [Paris|London|Rome]."></textarea>
                    <div class="mt-3">
                        <label class="form-label">Preview</label>
                        <div id="fillPreview" class="border p-2 bg-light"></div>
                    </div>
                </div>

                <!-- Correct Answer Field (MCQ only) -->
                <div class="mb-3 mt-3" id="correctAnswerWrapper">
                    <label class="form-label">Correct Answer</label>
                    <input type="text" name="correct_answer" class="form-control" id="correctAnswerInput">
                </div>

                <div class="mb-3">
                    <label class="form-label">Hint (optional)</label>
                    <textarea name="hint" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Explanation (optional)</label>
                    <textarea name="explanation" class="form-control"></textarea>
                </div>

                <button type="submit" class="btn btn-success">Save Question</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleQuestionType() {
    const type = document.querySelector("select[name='type']").value;

    document.getElementById("mcq-options").style.display = (type === 'mcq') ? 'block' : 'none';
    document.getElementById("trueFalseOptions").style.display = (type === 'true_false') ? 'block' : 'none';
    document.getElementById("matching-options").style.display = (type === 'matching') ? 'block' : 'none';
    document.getElementById("fill-blank-options").style.display = (type === 'fill_blank_dropdown') ? 'block' : 'none';
    document.getElementById("correctAnswerWrapper").style.display = (type === 'mcq') ? 'block' : 'none';

    if (type === 'true_false') {
        document.getElementById("correctAnswerInput").value = ""; // clear MCQ answer input
    }

    updatePreview?.();
    updateFillPreview?.();
}

function addMatchPair() {
    const pairList = document.getElementById("pair-list");
    const row = document.createElement('div');
    row.className = 'row mb-2';
    row.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="column_a[]" class="form-control" placeholder="Column A" oninput="updatePreview()">
        </div>
        <div class="col-md-5">
            <input type="text" name="column_b[]" class="form-control" placeholder="Column B" oninput="updatePreview()">
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.parentElement.remove(); updatePreview();">✖</button>
        </div>
    `;
    pairList.appendChild(row);
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
    const input = document.getElementById("fillTextInput").value;
    let rendered = input.replace(/\[([^\[\]]+)\]/g, (match, options) => {
        const opts = options.split('|').map(opt => `<option>${opt.trim()}</option>`).join('');
        return `<select class='form-select d-inline w-auto mx-1'>${opts}</select>`;
    });
    document.getElementById("fillPreview").innerHTML = rendered;
}

document.addEventListener('DOMContentLoaded', toggleQuestionType);
</script>

<?php require_once '../templates/footer.php'; ?>
