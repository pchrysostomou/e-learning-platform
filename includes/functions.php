<?php
//add error logging 
ini_set('log_errors', 1);
ini_set('display_errors', 0); // hide from browser
ini_set('error_log', __DIR__ . '/../logs/app_errors.log');


if (!function_exists('base_url')) {
    function base_url($path = '') {
        // Load .env only if not already loaded
        if (!isset($_ENV['BASE_URL'])) {
            // Include Composer autoloader
            if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
                require_once __DIR__ . '/../vendor/autoload.php';
                $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
                $dotenv->load();
            } else {
                die("Composer autoloader not found. Run `composer install`.");
            }
        }

        return rtrim($_ENV['BASE_URL'], '/') . '/' . ltrim($path, '/');
    }
}


if (!function_exists('is_active')) {
    function is_active($path) {
        $current = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($current, $path) !== false ? 'active' : '';
    }
}


/**
 * Logs a user activity to the user_activity_log table
 *
 * @param int $user_id The user performing the action
 * @param string $activity The description of the activity
 */
if (!function_exists('log_activity')) {
    function log_activity($user_id, $activity) {
        global $pdo;

        // Prepare and execute insert query
        $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, activity) VALUES (?, ?)");
        $stmt->execute([$user_id, $activity]);
    }
}
function format_json_answer_pretty($json, $type) {
    if (!in_array($type, ['matching', 'fill_blank_dropdown']) || !is_json($json)) {
        return htmlspecialchars($json);
    }

    $pairs = json_decode($json, true);
    $html = '<ul class="mb-0 ps-3">';
    foreach ($pairs as $pair) {
        if (is_array($pair)) {
            $left = htmlspecialchars($pair['left'] ?? '');
            $right = htmlspecialchars($pair['right'] ?? '');
            $html .= "<li>{$left} → {$right}</li>";
        } else {
            $html .= '<li>' . htmlspecialchars((string)$pair) . '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
}
