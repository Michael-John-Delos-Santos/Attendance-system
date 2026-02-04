<?php
require_once 'config.php';
requireLogin();

$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['Admin','Owner'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $faculty_id = $_POST['faculty_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($faculty_id && in_array($action, ['in','out'])) {
        $pdo = getDBConnection();
        $time = date('H:i:s');
        $today = date('Y-m-d');

        if ($action === 'in') {
            $stmt = $pdo->prepare("
                INSERT INTO faculty_attendance (faculty_id, attendance_date, time_in)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE time_in=?
            ");
            $stmt->execute([$faculty_id, $today, $time, $time]);
            $_SESSION['success'] = "Clocked in at $time";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO faculty_attendance (faculty_id, attendance_date, time_out)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE time_out=?
            ");
            $stmt->execute([$faculty_id, $today, $time, $time]);
            $_SESSION['success'] = "Clocked out at $time";
        }
    } else {
        $_SESSION['error'] = "Please select faculty and action.";
    }
}

header("Location: faculty.php");
exit;
?>
