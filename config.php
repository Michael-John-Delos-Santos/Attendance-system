<?php
// Database Settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'attendance_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// School Settings
define('SCHOOL_NAME', 'School of Saint Maximillian Mary Kolbe');

// 1. SAFE SESSION START
// This fixes the "Ignoring session_start()" error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Database Connection
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

// 3. Admin Authentication Check
function requireAdmin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// 4. Verify Admin Key (For sensitive actions)
function verifyAdminKey($inputKey) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT admin_key FROM admin_config WHERE id = 1");
    $stmt->execute();
    $config = $stmt->fetch();
    return ($config && $inputKey === $config['admin_key']);
}
?>