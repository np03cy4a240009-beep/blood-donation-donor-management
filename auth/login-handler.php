<?php
require_once("../includes/security.php");
include("../config/db.php");
include("../config/env-loader.php");
include("send-otp-mail.php");

date_default_timezone_set('UTC');

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['login_error'] = $message;
    header("Location: ../login.php");
    exit();
}

$emailOrPhone = trim($_POST['email_or_phone'] ?? '');
$password = trim($_POST['password'] ?? '');

error_log("Login attempt for: $emailOrPhone");

if ($emailOrPhone === '' || $password === '') {
    redirectWithError("Email/Phone and password are required.");
}

// Check if input is email or phone
$isEmail = filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL);
$isPhone = preg_match('/^[0-9]{10}$/', $emailOrPhone);

if (!$isEmail && !$isPhone) {
    redirectWithError("Please enter a valid email address or 10-digit phone number.");
}

// Query user by email or phone
if ($isEmail) {
    $emailOrPhone = normalizeEmail($emailOrPhone);
    $stmt = $conn->prepare("SELECT id, role, full_name, email, password, hospital_name FROM users WHERE email = ? LIMIT 1");
} else {
    $stmt = $conn->prepare("SELECT id, role, full_name, email, password, hospital_name FROM users WHERE phone = ? LIMIT 1");
}

$stmt->bind_param("s", $emailOrPhone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    redirectWithError("Invalid email/phone or password. Please try again.");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    redirectWithError("Invalid email/phone or password. Please try again.");
}

// Password verified - Send OTP for login verification
$otp = (string)random_int(100000, 999999);
$otpHash = hash('sha256', $otp);
// use DB-side expiry to avoid timezone mismatches
// expires_at will be set in the INSERT using DATE_ADD(NOW(), INTERVAL 20 MINUTE)

// Map bloodlinehome@gmail.com to actual receiving email for OTP storage
$otpEmail = $user['email'];
if (strtolower(trim($user['email'])) === 'bloodlinehome@gmail.com') {
    $otpEmail = 'gckapil64@gmail.com';
}

// Delete old OTP codes for this email
$deleteOld = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteOld->bind_param("s", $otpEmail);
$deleteOld->execute();

// Insert new OTP with actual receiving email; set expires_at in DB to avoid timezone issues
$insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE))");
$insertOtp->bind_param("ss", $otpEmail, $otpHash);

if (!$insertOtp->execute()) {
    error_log("Failed to insert OTP into database for {$otpEmail}: " . $insertOtp->error);
    redirectWithError("Failed to generate OTP. Please try again.");
}

error_log("OTP generated and stored for: {$otpEmail}");

// Store user info in session before email sending so the verify flow is ready
// even if SMTP takes a few seconds to respond.
$_SESSION['login_user_id'] = $user['id'];
$_SESSION['login_user_role'] = $user['role'];
$_SESSION['login_user_full_name'] = $user['full_name'];
$_SESSION['login_user_email'] = $user['email'];
$_SESSION['login_user_hospital_name'] = $user['hospital_name'] ?? '';
$_SESSION['login_otp_email'] = $otpEmail;
$_SESSION['login_otp_started_at'] = time();
$_SESSION['otp_attempts'] = 0;

error_log("Sending OTP email to {$user['email']} (delivery inbox: {$otpEmail})");
$emailSent = sendOtpMail($user['email'], $otp);
if ($emailSent) {
    error_log("✓ OTP email sent successfully to {$user['email']}");
} else {
    error_log("✗ OTP email failed for {$user['email']}, but login continues (user can resend)");
}

$_SESSION['login_otp_success'] = "OTP has been sent to your email. Please check the latest code.";

regenerateCsrfToken();

// Redirect to OTP verification page
header("Location: ../verify-otp-login.php");
exit();
?>
