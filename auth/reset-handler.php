<?php
// Set UTC timezone FIRST before any database operations
date_default_timezone_set('UTC');

require_once("../includes/security.php");
include("../config/db.php");

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['reset_error'] = $message;
    header("Location: ../reset-password.php");
    exit();
}

if (!isset($_SESSION['reset_email'])) {
    redirectWithError("Session expired. Please request OTP again.");
}

$email = normalizeEmail($_SESSION['reset_email']);
$otp = trim($_POST['otp'] ?? '');
$new_password = $_POST['new_password'] ?? '';

// Validate OTP format - must be exactly 6 digits
if ($otp === '' || $new_password === '') {
    redirectWithError("All fields are required.");
}

if (!preg_match('/^\d{6}$/', $otp)) {
    redirectWithError("OTP must be exactly 6 digits. Please check and try again.");
}

if (!strongPassword($new_password)) {
    redirectWithError("Password must be at least 8 characters long, contain at least 1 special character, and include letters or numbers.");
}

$password_pattern = '/^(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/';
if (!preg_match($password_pattern, $new_password)) {
    redirectWithError("Password must be at least 8 characters long, contain at least 1 special character (!@#$%^&*...), and include letters or numbers.");
}

// Retrieve OTP record - database NOW() uses UTC timezone
$sql = "SELECT otp_code FROM otp_codes WHERE email = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("DEBUG OTP NOT FOUND: Email=$email | CurrentTime=" . date("Y-m-d H:i:s"));
    redirectWithError("Invalid or expired OTP. Please request a new OTP.");
}

$row = $result->fetch_assoc();

// Verify OTP using SHA256
$otpHash = hash('sha256', $otp);
error_log("DEBUG OTP VERIFY: Input=$otp | InputHash=$otpHash | StoredHash=" . $row['otp_code'] . " | Email=$email");

if ($otpHash !== $row['otp_code']) {
    error_log("DEBUG OTP MISMATCH: Generated hash does not match stored hash");
    redirectWithError("Invalid OTP. Please try again.");
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$update->bind_param("ss", $hashed, $email);

if ($update->execute()) {
    $deleteOtp = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
    $deleteOtp->bind_param("s", $email);
    $deleteOtp->execute();

    unset($_SESSION['reset_email']);
    regenerateCsrfToken();

    $_SESSION['password_reset_success'] = "Password has been reset successfully! You can now login with your new password.";
    header("Location: ../login.php");
    exit();
}

redirectWithError("Password update failed. Please try again.");
?>