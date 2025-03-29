<?php
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
