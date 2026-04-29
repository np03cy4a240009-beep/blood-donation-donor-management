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
    header("Location: ../auth/verify-otp-register.php");
    exit();
}

function redirectToRegister($message) {
    $_SESSION['registration_error'] = $message;
    header("Location: ../register.php");
    exit();
}

// Check if registration data is in session
if (!isset($_SESSION['register_data']) || !isset($_SESSION['register_email']) || !isset($_SESSION['register_otp'])) {
    redirectToRegister("Session expired. Please register again.");
}

$otp = trim($_POST['otp'] ?? '');

if ($otp === '') {
    redirectWithError("OTP is required.");
}

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    redirectWithError("OTP must be exactly 6 digits.");
}

// Check OTP attempts
if (!isset($_SESSION['register_otp_attempts'])) {
    $_SESSION['register_otp_attempts'] = 0;
}

if ($_SESSION['register_otp_attempts'] >= 5) {
    unset($_SESSION['register_data']);
    unset($_SESSION['register_email']);
    unset($_SESSION['register_otp']);
    unset($_SESSION['register_otp_attempts']);
    redirectToRegister("Too many failed OTP attempts. Please register again.");
}

// Verify OTP from database
$email = $_SESSION['register_email'];
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
    $_SESSION['register_otp_attempts']++;
    redirectWithError("Invalid or expired OTP. Please try again.");
}

// OTP verified successfully, insert user into database
$registerData = $_SESSION['register_data'];

$sql = "INSERT INTO users
(role, full_name, email, password, phone, age, weight, gender, address, city, province, zip_code, blood_group, medical_history, eligibility_status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    redirectWithError("Database error: " . $conn->error);
}

$stmt->bind_param(
    "sssssddssssssss",
    $registerData['role'],
    $registerData['full_name'],
    $registerData['email'],
    $registerData['password_hash'],
    $registerData['phone'],
    $registerData['age'],
    $registerData['weight'],
    $registerData['gender'],
    $registerData['address'],
    $registerData['city'],
    $registerData['province'],
    $registerData['zip_code'],
    $registerData['blood_group'],
    $registerData['medical_history'],
    $registerData['eligibility_status']
);

if (!$stmt->execute()) {
    error_log("Registration error: " . $stmt->error);
    redirectWithError("Registration failed. Please try again.");
}

// Delete used OTP
$deleteStmt = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteStmt->bind_param("s", $email);
$deleteStmt->execute();

// Clear registration session variables
unset($_SESSION['register_data']);
unset($_SESSION['register_email']);
unset($_SESSION['register_otp']);
unset($_SESSION['register_otp_attempts']);

// Set success message and redirect to login
regenerateCsrfToken();
$_SESSION['registration_success'] = "Your email has been successfully verified! You can now log in.";
header("Location: ../login.php");
exit();
?>
