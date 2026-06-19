<?php
session_start();

// Elastic Beanstalk injects RDS credentials as environment variables
$host = getenv('RDS_HOSTNAME') ?: 'localhost';
$port = getenv('RDS_PORT')     ?: '5432';
$db   = getenv('RDS_DB_NAME')  ?: 'apexopsdb';
$user = getenv('RDS_USERNAME') ?: 'apexops_admin';
$pass = getenv('RDS_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
