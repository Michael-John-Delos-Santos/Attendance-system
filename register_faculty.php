<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/attendance-system/config.php';
requireLogin();

$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['Admin','Owner'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($username && $full_name && $password) {
        $pdo = getDBConnection();

        // Check if username exists
        $stmt = $pdo->prepare("SELECT * FROM faculty WHERE username=?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Username already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO faculty (username, full_name, password, role, status) VALUES (?, ?, ?, ?, 'Active')");
            $stmt->execute([$username, $full_name, password_hash($password, PASSWORD_DEFAULT), $role]);
            $_SESSION['success'] = "Faculty registered successfully!";
        }
    } else {
        $_SESSION['error'] = "Please fill all fields.";
    }
}

header("Location: faculty.php");
exit;
?>
