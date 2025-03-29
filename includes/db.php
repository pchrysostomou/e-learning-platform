<?php
// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php'; // Include Composer autoload

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); // Point to your root dir
$dotenv->load(); // Load the .env file

// Now get the database variables from the .env file
$host = $_ENV['DB_HOST'];
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$charset = $_ENV['DB_CHARSET'];

// Setup DSN and PDO options
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Try to connect to the database
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
