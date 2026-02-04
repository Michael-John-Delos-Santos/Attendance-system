<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'attendance_db');

// SMTP configuration for email notifications
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'michaelapril81416@gmail.com');
define('SMTP_PASSWORD', 'xqhseaxhhpsyzkld');
define('SMTP_FROM_EMAIL', 'michaelapril81416@gmail.com');
define('SMTP_FROM_NAME', 'School of St. Maximilian Mary Kolbe');

// Application settings
define('TIMEZONE', 'Asia/Manila');
define('SCHOOL_NAME', 'School of St. Maximilian Mary Kolbe, Inc.');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Database connection
function getDBConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Helper function to generate QR token (short unique token)
function generateQRToken() {
    return substr(uniqid(), 0, 10);
}

// Helper function to validate QR token
function validateQRToken($qr_token) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE qr_token = ? AND status = 'Active'");
    $stmt->execute([$qr_token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Session management
session_start();

function isLoggedIn() {
    return isset($_SESSION['faculty_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM faculty WHERE faculty_id = ?");
    $stmt->execute([$_SESSION['faculty_id']]);
    return $stmt->fetch();
}
?>
