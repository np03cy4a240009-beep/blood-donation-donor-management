<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set UTC timezone FIRST before any database operations
date_default_timezone_set('UTC');

require_once("../includes/security.php");
include("../config/db.php");
include("send-otp-mail.php");

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['otp_error'] = $message;
    header("Location: ../forgot-password.php");
    exit();
}

try {
    $email = normalizeEmail($_POST['email'] ?? '');

    if ($email === '') {
        redirectWithError("Email is required.");
    }

    if (!isValidEmail($email)) {
        redirectWithError("Invalid email address.");
    }

    $checkUser = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    if (!$checkUser) {
        redirectWithError("Database error. Please try again.");
    }
    
    $checkUser->bind_param("s", $email);
    $checkUser->execute();
    $result = $checkUser->get_result();

    if ($result->num_rows === 0) {
        redirectWithError("No account found with this email.");
    }

    $otp = (string)random_int(100000, 999999);
    // Use SHA256 hash for OTP
    $otpHash = hash('sha256', $otp);
    // OTP valid for 1 minute
    $expires_at = date("Y-m-d H:i:s", strtotime("+1 minute"));

    $deleteOld = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
    if (!$deleteOld) {
        redirectWithError("Database error. Please try again.");
    }
    $deleteOld->bind_param("s", $email);
    $deleteOld->execute();

    $insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
    if (!$insertOtp) {
        redirectWithError("Database error. Please try again.");
    }
    $insertOtp->bind_param("sss", $email, $otpHash, $expires_at);

    if (!$insertOtp->execute()) {
        redirectWithError("Failed to generate OTP. Please try again.");
    }

    // Send OTP
    sendOtpMail($email, $otp);
    
    $_SESSION['reset_email'] = $email;
    $_SESSION['otp_demo'] = $otp;  // Demo OTP for development - remove in production
    $_SESSION['otp_success'] = "OTP has been sent to your email. Please check and enter the code. (Valid for 1 minute)";
    regenerateCsrfToken();
    header("Location: ../reset-password.php");
    exit();
    
} catch (Exception $e) {
    error_log("OTP Handler Error: " . $e->getMessage());
    redirectWithError("An error occurred. Please try again.");
}
?>