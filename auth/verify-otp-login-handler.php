<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

date_default_timezone_set('UTC');

require_once("../includes/security.php");
include("../config/db.php");

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['otp_error'] = $message;
    header("Location: ../auth/verify-otp-login.php");
    exit();
}

function redirectToLogin($message) {
    $_SESSION['login_error'] = $message;
    header("Location: ../login.php");
    exit();
}

// Check if user has initiated OTP login
if (!isset($_SESSION['otp_login_email']) || !isset($_SESSION['otp_user_id'])) {
    redirectToLogin("Session expired. Please login again.");
}

$otp = trim($_POST['otp'] ?? '');

if ($otp === '') {
    redirectWithError("OTP is required.");
}

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    redirectWithError("OTP must be exactly 6 digits.");
}

// Check OTP attempts
if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

if ($_SESSION['otp_attempts'] >= 5) {
    unset($_SESSION['otp_login_email']);
    unset($_SESSION['otp_user_id']);
    unset($_SESSION['otp_hash']);
    unset($_SESSION['otp_expires']);
    unset($_SESSION['otp_attempts']);
    redirectToLogin("Too many failed OTP attempts. Please login again.");
}

// Verify OTP from database
$email = $_SESSION['otp_login_email'];
$otpHash = hash('sha256', $otp);

$stmt = $conn->prepare("
    SELECT id FROM otp_codes 
    WHERE email = ? AND otp_code = ? AND expires_at > NOW()
    LIMIT 1
");

if (!$stmt) {
    redirectWithError("Database error. Please try again.");
}

$stmt->bind_param("ss", $email, $otpHash);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['otp_attempts']++;
    // Check if OTP is wrong or expired
    $checkExpired = $conn->prepare("SELECT id FROM otp_codes WHERE email = ? AND otp_code = ?");
    $checkExpired->bind_param("ss", $email, $otpHash);
    $checkExpired->execute();
    $expiredResult = $checkExpired->get_result();
    
    if ($expiredResult->num_rows > 0) {
        redirectWithError("OTP has expired. Please login again.");
    } else {
        redirectWithError("Invalid OTP. Please try again.");
    }
}

// OTP verified successfully, get user info and log in
$user_id = $_SESSION['otp_user_id'];

$userStmt = $conn->prepare("SELECT id, role, full_name, email FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows !== 1) {
    redirectToLogin("User not found. Please login again.");
}

$user = $userResult->fetch_assoc();

// Delete used OTP
$deleteStmt = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteStmt->bind_param("s", $email);
$deleteStmt->execute();

// Clear OTP session variables
unset($_SESSION['otp_login_email']);
unset($_SESSION['otp_user_id']);
unset($_SESSION['otp_hash']);
unset($_SESSION['otp_expires']);
unset($_SESSION['otp_attempts']);

// Regenerate session and set user session
session_regenerate_id(true);
regenerateCsrfToken();

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];

if ($user['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

header("Location: ../user/dashboard.php");
exit();
?>
