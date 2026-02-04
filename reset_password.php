<?php
require_once 'config.php';
requireLogin();

$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['Admin','Owner'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = $_POST['faculty_id'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    if ($faculty_id && $new_password) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE faculty SET password=? WHERE faculty_id=?");
        $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $faculty_id]);
        $_SESSION['success'] = "Password reset successfully!";
    } else {
        $_SESSION['error'] = "Please select faculty and enter a new password.";
    }
}

header("Location: faculty.php");
exit;
?>
