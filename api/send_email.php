<?php
require_once '../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // adjust path to your vendor folder

function sendAttendanceEmail($student, $status, $time_in) {
    try {
        $mail = new PHPMailer(true);

        $student_name = $student['first_name'] . ' ' . $student['last_name'];
        $time_formatted = date('g:i A', strtotime($time_in));
        $date_formatted = date('F j, Y');

        $subject = "Attendance Notification - {$student_name}";

        $message = "
            <p>Dear {$student['parent_name']},</p>
            <p>We hope this message finds you well.</p>
            <p>This is to formally notify you that your child, <b>{$student_name}</b>, enrolled in <b>{$student['grade_section']}</b>, 
            has been marked <b>{$status}</b> on <b>{$date_formatted}</b> at <b>{$time_formatted}</b>.</p>
            <p>We encourage punctuality and regular attendance as it is essential for your child's academic progress.</p>
            <p>Thank you for your continued support and cooperation.</p>
            <p>Sincerely,<br>" . SCHOOL_NAME . "</p>
        ";

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($student['parent_email'], $student['parent_name']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();

        // Log success
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (student_id, parent_email, message, status) VALUES (?, ?, ?, 'Sent')");
        $stmt->execute([$student['student_id'], $student['parent_email'], $message]);

        return 'Sent';
    } catch (Exception $e) {
        // Log failure
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (student_id, parent_email, message, status) VALUES (?, ?, ?, 'Failed')");
        $stmt->execute([$student['student_id'], $student['parent_email'], $e->getMessage()]);

        return 'Failed';
    }
}
?>
