<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 

function sendAttendanceEmail($student, $type, $time, $status) {
    // 1. Fetch Config from DB
    $pdo = getDBConnection();
    $config = $pdo->query("SELECT * FROM admin_config WHERE id = 1")->fetch();

    if (empty($config['smtp_email']) || empty($config['smtp_password'])) {
        error_log("Email Skipped: SMTP credentials not set.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // 2. Server Settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_email'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // 3. Recipients
        $mail->setFrom($config['smtp_email'], 'School Attendance System');
        $mail->addAddress($student['parent_email'], $student['parent_name']);

        // 4. Content Preparation
        $mail->isHTML(true);
        $studentName = $student['first_name'] . ' ' . $student['last_name'];
        $timeFormatted = date('g:i A', strtotime($time));
        $dateFormatted = date('F j, Y');

        // Select Template based on Type
        if ($type === 'in') {
            $subjectTemplate = $config['template_subject_in'];
            $bodyTemplate = $config['template_body_in'];
        } else {
            $subjectTemplate = $config['template_subject_out'];
            $bodyTemplate = $config['template_body_out'];
        }

        // 5. Replace Placeholders
        $placeholders = [
            '{student_name}' => $studentName,
            '{parent_name}'  => $student['parent_name'],
            '{time}'         => $timeFormatted,
            '{date}'         => $dateFormatted,
            '{status}'       => $status
        ];

        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subjectTemplate);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $bodyTemplate);

        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>