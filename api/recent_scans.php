<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT 
            CONCAT(s.first_name, ' ', s.last_name) as student_name,
            s.grade_section,
            a.time_in,
            a.status
        FROM attendance a 
        JOIN students s ON a.student_id = s.student_id 
        WHERE a.attendance_date = ? 
        ORDER BY a.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$today]);
    $scans = $stmt->fetchAll();
    
    // Format time for display
    foreach ($scans as &$scan) {
        $scan['time_in'] = date('g:i A', strtotime($scan['time_in']));
    }
    
    echo json_encode($scans);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
