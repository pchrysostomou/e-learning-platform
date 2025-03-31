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
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    $_SESSION['error'] = "Course not specified.";
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

// ✅ Verify enrollment
$stmt = $pdo->prepare("SELECT c.* FROM courses c 
    JOIN enrollments e ON e.course_id = c.id 
    WHERE c.id = ? AND e.student_id = ?");
$stmt->execute([$course_id, $student_id]);
$course = $stmt->fetch();

if (!$course) {
    $_SESSION['error'] = "Access denied or not enrolled.";
    header("Location: " . base_url("users/dashboard.php"));
    exit;
}

// ✅ Get modules
$stmt = $pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY created_at ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

$page_title = "Course Modules";
include '../templates/header.php';
?>

<h2 class="mb-4">📘 Modules for <?= htmlspecialchars($course['title']) ?></h2>

<?php if ($modules): ?>
    <?php foreach ($modules as $mod): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($mod['title']) ?></h5>
                <p class="card-text"><?= nl2br(htmlspecialchars($mod['content'])) ?></p>
                <p class="text-muted small">Created on <?= date('Y-m-d H:i', strtotime($mod['created_at'])) ?></p>

                <?php
                $m_stmt = $pdo->prepare("SELECT * FROM module_materials WHERE module_id = ?");
                $m_stmt->execute([$mod['id']]);
                $materials = $m_stmt->fetchAll();
                ?>

                <?php if ($materials): ?>
                    <div class="mt-3">
                        <strong>📎 Materials:</strong>
                        <ul class="list-group list-group-flush mt-2">
                            <?php foreach ($materials as $mat): ?>
                                <?php
                                    $ext = strtolower(pathinfo($mat['file_name'], PATHINFO_EXTENSION));
                                    $size_kb = isset($mat['size']) ? number_format($mat['size'] / 1024, 1) : 'N/A';
                                    $icon = '📄';
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = '🖼';
                                    elseif ($ext === 'pdf') $icon = '📕';
                                    elseif (in_array($ext, ['doc', 'docx'])) $icon = '📝';
                                    elseif (in_array($ext, ['ppt', 'pptx'])) $icon = '📊';
                                ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?= $icon ?>
                                        <a href="<?= base_url("users/download.php?file_id=" . $mat['id']) ?>" target="_blank">
                                            <?= htmlspecialchars($mat['original_name'] ?: $mat['filename']) ?>
                                        </a>
                                        <span class="text-muted small">(<?= strtoupper($ext) ?>, <?= $size_kb ?> KB)</span><br>
                                        <span class="text-muted small">⬇ Downloads: <?= (int)$mat['download_count'] ?></span>
                                    </div>
                                    <a href="<?= base_url("users/download.php?file_id=" . $mat['id']) ?>" class="btn btn-sm btn-outline-primary" download>
                                        ⬇ Download
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="alert alert-info">No modules available yet.</div>
<?php endif; ?>

<?php include '../templates/footer.php'; ?>
