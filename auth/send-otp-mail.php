<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendOtpMail($toEmail, $otp) {
    $smtpUser = getenv('MAIL_USERNAME');
    $smtpPass = getenv('MAIL_PASSWORD');
    $fromEmail = getenv('MAIL_FROM') ?: $smtpUser;
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Bloodline Home';

    if (!$smtpUser || !$smtpPass) {
        error_log('Missing mail credentials.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your Bloodline Home OTP Code';
        $mail->Body = "<h2>Your OTP is: " . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</h2><p>This code expires in 10 minutes.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('OTP mail failed: ' . $e->getMessage());
        return false;
    }
}
?>
<?php // OTP logic version 1.1 ?>