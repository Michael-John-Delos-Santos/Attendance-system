<?php
require_once '../config.php';
require_once 'send_email.php'; 
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$identifier = $input['qr_token'] ?? '';

if (empty($identifier)) {
    echo json_encode(['success' => false, 'message' => 'No ID/QR provided']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Find Student
    $stmt = $pdo->prepare("SELECT * FROM students WHERE (qr_token = ? OR student_id_number = ?) AND status='Active'");
    $stmt->execute([$identifier, $identifier]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student Not Found']);
        exit;
    }

    $today = date('Y-m-d');
    $now = date('H:i:s');

    // 2. Check for Existing Attendance
    $checkStmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND attendance_date = ?");
    $checkStmt->execute([$student['student_id'], $today]);
    $attendance = $checkStmt->fetch();

    // 3. Get Schedule & Global Restrictions
    $schedStmt = $pdo->prepare("SELECT start_time, end_time FROM grade_settings WHERE grade_level = ?");
    $schedStmt->execute([$student['grade_level']]);
    $schedule = $schedStmt->fetch();

    $configStmt = $pdo->query("SELECT dismissal_window FROM admin_config WHERE id = 1");
    $config = $configStmt->fetch();
    $dismissalWindow = $config['dismissal_window'] ?? 45; // Default 45 mins

    // Defaults
    $lateTime = $schedule['start_time'] ?? '08:00:00';
    $endTimeStr = $schedule['end_time'] ?? '15:00:00';

    if (!$attendance) {
        // --- CASE A: TIME IN ---
        $status = ($now <= $lateTime) ? 'Present' : 'Late';
        
        $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, attendance_date, time_in, status) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$student['student_id'], $today, $now, $status]);

        if (!empty($student['parent_email'])) {
            sendAttendanceEmail($student, 'in', $now, $status);
        }

        $timeFmt = date('g:i A', strtotime($now));
        echo json_encode(['success' => true, 'message' => "TIME IN: {$student['first_name']} ($status - $timeFmt)"]);

    } else {
        // --- CASE B: TIME OUT ---
        
        if ($attendance['time_out'] == NULL) {
            
            // --- RESTRICTION LOGIC ---
            // Calculate Earliest Allowed Time Out
            // Logic: End Time minus X minutes
            $endTimeObj = new DateTime($today . ' ' . $endTimeStr);
            $earliestOutObj = clone $endTimeObj;
            $earliestOutObj->modify("-{$dismissalWindow} minutes");
            
            $nowObj = new DateTime($today . ' ' . $now);

            // Check if TOO EARLY
            if ($nowObj < $earliestOutObj) {
                $allowedTime = $earliestOutObj->format('g:i A');
                $endTimeFmt = $endTimeObj->format('g:i A');
                echo json_encode([
                    'success' => false, 
                    'message' => "Too Early! Dismissal is at $endTimeFmt. You can scan out starting $allowedTime."
                ]);
                exit;
            }
            
            // Allow Time Out
            $updateStmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE attendance_id = ?");
            $updateStmt->execute([$now, $attendance['attendance_id']]);

            if (!empty($student['parent_email'])) {
                sendAttendanceEmail($student, 'out', $now, 'Checked Out');
            }

            $timeFmt = date('g:i A', strtotime($now));
            echo json_encode(['success' => true, 'message' => "TIME OUT: {$student['first_name']} ($timeFmt)"]);
        
        } else {
            echo json_encode(['success' => false, 'message' => "Already completed attendance."]);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>