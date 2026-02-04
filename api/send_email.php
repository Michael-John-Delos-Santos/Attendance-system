<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 

function sendAttendanceEmail($student, $type, $time, $status) {
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS (Replace with your actual Gmail App Password) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-school-email@gmail.com'; // CHANGE THIS
        $mail->Password   = 'xxxx xxxx xxxx xxxx';         // CHANGE THIS (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your-school-email@gmail.com', 'School Attendance System');
        $mail->addAddress($student['parent_email'], $student['parent_name']);

        // Content
        $mail->isHTML(true);
        $studentName = $student['first_name'] . ' ' . $student['last_name'];
        $timeFormatted = date('g:i A', strtotime($time));
        $dateFormatted = date('F j, Y');

        if ($type === 'in') {
            // TIME IN EMAIL
            $color = ($status == 'Late') ? '#d97706' : '#16a34a';
            $mail->Subject = "Arrival Alert: $studentName";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;'>
                    <h2 style='color: $color;'>Student Arrival</h2>
                    <p>Dear <strong>{$student['parent_name']}</strong>,</p>
                    <p>Your child, <strong>$studentName</strong>, has arrived at school.</p>
                    <ul>
                        <li><strong>Time In:</strong> $timeFormatted</li>
                        <li><strong>Status:</strong> $status</li>
                        <li><strong>Date:</strong> $dateFormatted</li>
                    </ul>
                    <p>Safe and sound!<br>School Administration</p>
                </div>";
        } else {
            // TIME OUT EMAIL
            $mail->Subject = "Departure Alert: $studentName";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd;'>
                    <h2 style='color: #2563eb;'>Student Departure</h2>
                    <p>Dear <strong>{$student['parent_name']}</strong>,</p>
                    <p>Your child, <strong>$studentName</strong>, has left the school premises.</p>
                    <ul>
                        <li><strong>Time Out:</strong> $timeFormatted</li>
                        <li><strong>Date:</strong> $dateFormatted</li>
                    </ul>
                    <p>See you tomorrow!<br>School Administration</p>
                </div>";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>