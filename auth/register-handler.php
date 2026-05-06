<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set UTC timezone FIRST before any database operations
date_default_timezone_set('UTC');

// Set up error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return false;
});

// Set up exception handler
set_exception_handler(function($e) {
    error_log("Exception: " . $e->getMessage());
    $_SESSION['registration_error'] = "An error occurred: " . $e->getMessage();
    header("Location: ../register.php");
    exit();
});

require_once("../includes/security.php");
include("../config/db.php");
include("send-otp-mail.php");

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['registration_error'] = $message;
    header("Location: ../register.php");
    exit();
}

$role = trim($_POST['role'] ?? 'user');
$full_name = trim($_POST['full_name'] ?? '');
$email = normalizeEmail($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
    redirectWithError("Please fill all required fields.");
}

if (!isValidEmail($email)) {
    redirectWithError("Invalid email address.");
}

if (!in_array($role, ['admin', 'user'], true)) {
    $role = 'user';
}

if ($password !== $confirm_password) {
    redirectWithError("Passwords do not match.");
}

if (!strongPassword($password)) {
    redirectWithError("Password must be at least 8 characters long, contain at least 1 special character, and include letters or numbers.");
}

$password_pattern = '/^(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/';
if (!preg_match($password_pattern, $password)) {
    redirectWithError("Password must be at least 8 characters long, contain at least 1 special character (!@#$%^&*...), and include letters or numbers.");
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$exists = $check->get_result();

if ($exists->num_rows > 0) {
    redirectWithError("This email or number is already registered.");
}


$phone = trim($_POST['phone'] ?? '');
$age = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
$weight = ($_POST['weight'] ?? '') !== '' ? (float)$_POST['weight'] : null;
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$medical_history = trim($_POST['medical_history'] ?? '');
$eligibility_status = 'eligible';

// Validate phone number: only digits, exactly 10 digits
if ($role === 'user') {
    if ($phone === '' || !preg_match('/^[0-9]{10}$/', $phone)) {
        redirectWithError("Phone number must be exactly 10 digits. No minus sign allowed.");
    }
    
    // Check if phone number is already registered
    $checkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    $phoneExists = $checkPhone->get_result();
    
    if ($phoneExists->num_rows > 0) {
        redirectWithError("This email or number is already registered.");
    }
    
    // Age validation for users (must be 18 or older)
    if ($age === null || $age < 18) {
        redirectWithError("You must be at least 18 years old to be a donor.");
    }
}

if ($role === 'admin') {
    $phone = '';
    $age = null;
    $weight = null;
    $gender = '';
    $address = '';
    $city = '';
    $province = '';
    $zip_code = '';
    $blood_group = '';
    $medical_history = '';
} else {
    // User role fields handled above
}

// Debug log
error_log("Registration data - Role: $role, Email: $email, Phone: '$phone', Age: " . var_export($age, true) . ", Weight: " . var_export($weight, true));

// Store registration data in session for OTP verification
$_SESSION['register_data'] = [
    'role' => $role,
    'full_name' => $full_name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'phone' => $phone,
    'age' => $age,
    'weight' => $weight,
    'gender' => $gender,
    'address' => $address,
    'city' => $city,
    'province' => $province,
    'zip_code' => $zip_code,
    'blood_group' => $blood_group,
    'medical_history' => $medical_history,
    'eligibility_status' => 'eligible'
];

// Generate OTP for email verification
$otp = (string)random_int(100000, 999999);
$otpHash = hash('sha256', $otp);
$expires_at = date("Y-m-d H:i:s", strtotime("+1 minute"));

// Delete any existing OTP for this email
$deleteOtp = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteOtp->bind_param("s", $email);
$deleteOtp->execute();

// Insert new OTP
$insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
$insertOtp->bind_param("sss", $email, $otpHash, $expires_at);

if (!$insertOtp->execute()) {
    redirectWithError("Failed to generate OTP. Please try again.");
}

// Store OTP in session for demo display
$_SESSION['register_otp'] = $otp;
$_SESSION['register_email'] = $email;

// Send OTP email
if (!sendOtpMail($email, $otp)) {
    error_log("OTP email failed for $email, but continuing with session storage");
}

regenerateCsrfToken();
header("Location: ../auth/verify-otp-register.php");
exit();
?>