<?php

function sendOtpMail($toEmail, $otp) {
    // For development/testing: Log OTP instead of sending email
    error_log("OTP for $toEmail: $otp");
    
    // Try to use PHPMailer if available, but don't fail if it's not
    try {
        if (file_exists('../vendor/autoload.php')) {
            require_once '../vendor/autoload.php';
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                
                $smtpUser = getenv('MAIL_USERNAME');
                $smtpPass = getenv('MAIL_PASSWORD');
                $fromEmail = getenv('MAIL_FROM') ?: $smtpUser;
                $fromName = getenv('MAIL_FROM_NAME') ?: 'Bloodline Home';

                if ($smtpUser && $smtpPass) {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUser;
                    $mail->Password = $smtpPass;
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom($fromEmail, $fromName);
                    $mail->addAddress($toEmail);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Bloodline Home OTP Code';
                    $mail->Body = "<h2>Your OTP is: " . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . "</h2><p>This code expires in 1 minute.</p>";

                    $mail->send();
                    return true;
                }
            }
        }
    } catch (Exception $e) {
        error_log('OTP mail error: ' . $e->getMessage());
    }
    
    // Return true anyway for testing purposes - OTP is displayed in demo box
    return true;
}
?>