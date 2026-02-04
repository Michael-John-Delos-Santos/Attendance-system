<?php
require_once '../config.php';
require_once 'send_email.php'; // separate email handler
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$qr_token = $input['qr_token'] ?? '';

if (empty($qr_token)) {
    echo json_encode(['success' => false, 'message' => 'QR code is required']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Get student
    $stmt = $pdo->prepare("SELECT * FROM students WHERE qr_token = ? AND status='Active'");
    $stmt->execute([$qr_token]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Invalid QR code or inactive student']);
        exit;
    }

    $student_id = $student['student_id'];
    $today = date('Y-m-d');
    $current_time = date('H:i:s');

    // Check if attendance exists today
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id=? AND attendance_date=?");
    $stmt->execute([$student_id, $today]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Attendance already recorded for today']);
        exit;
    }

    // Determine status
    $status = ($current_time <= '08:00:00') ? 'Present' : 'Late';

    // Insert attendance
    $stmt = $pdo->prepare("INSERT INTO attendance (student_id, attendance_date, time_in, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$student_id, $today, $current_time, $status]);

    // Send email in the background (won’t break scanner)
    $email_status = 'No Email';
    if (!empty($student['parent_email'])) {
        $email_status = sendAttendanceEmail($student, $status, $current_time);
    }

    echo json_encode([
        'success' => true,
        'message' => "Attendance recorded for {$student['first_name']} {$student['last_name']} at " . date('g:i A', strtotime($current_time)) . " ({$status}) | Email: {$email_status}"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
