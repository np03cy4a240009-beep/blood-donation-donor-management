<?php
require_once("../includes/security.php");
include("../config/db.php");
include("send-otp-mail.php");

date_default_timezone_set('UTC');

secureSessionStart();

// Check if user has initiated OTP login
if (!isset($_SESSION['otp_login_email']) || !isset($_SESSION['otp_user_id'])) {
    header("Location: ../login.php");
    exit();
}

$email = $_SESSION['otp_login_email'];
$userId = $_SESSION['otp_user_id'];

// Generate new OTP
$otp = (string)random_int(100000, 999999);
$otpHash = hash('sha256', $otp);
$expires_at = date("Y-m-d H:i:s", strtotime("+1 minute"));  // 1 minute validity

// Reset attempts counter
$_SESSION['otp_attempts'] = 0;
$_SESSION['otp_demo'] = $otp;  // Store demo OTP that user will see and use
$_SESSION['otp_hash'] = $otpHash;
$_SESSION['otp_expires'] = $expires_at;

// Delete old OTP and insert new one
$deleteOtp = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteOtp->bind_param("s", $email);
$deleteOtp->execute();

$insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
$insertOtp->bind_param("sss", $email, $otpHash, $expires_at);

if (!$insertOtp->execute()) {
    $_SESSION['otp_error'] = "Failed to generate new OTP. Please login again.";
    header("Location: ../login.php");
    exit();
}

// Send OTP email
sendOtpMail($email, $otp);

// Redirect back to verify OTP page with new OTP
header("Location: ../auth/verify-otp-login.php");
exit();
?>
