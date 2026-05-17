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
include("../config/env-loader.php");
include("send-otp-mail.php");

secureSessionStart();
verifyCsrf();

function redirectWithError($message) {
    $_SESSION['registration_error'] = $message;
    header("Location: ../register.php");
    exit();
}

$role = 'user';
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

// Handle health card image upload for users
if ($role === 'user' && isset($_FILES['health_card']) && $_FILES['health_card']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['health_card'];
    
    // Validate file upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirectWithError("Error uploading health card image. Please try again.");
    }
    
    // Check file size (max 5MB)
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxFileSize) {
        redirectWithError("Health card image must be less than 5MB.");
    }
    
    // Validate file type
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!isset($allowedMimes[$mimeType])) {
        redirectWithError("Health card image must be a valid image file (JPG, PNG, GIF, WebP).");
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = dirname(__DIR__) . '/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $fileExtension = $allowedMimes[$mimeType];
    $fileName = 'health_card_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        redirectWithError("Failed to upload health card image. Please try again.");
    }
    
    // Store relative path for database
    $health_card = 'uploads/profiles/' . $fileName;
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
$emergency_email_1 = normalizeEmail($_POST['emergency_email_1'] ?? '');
$emergency_email_2 = normalizeEmail($_POST['emergency_email_2'] ?? '');
$emergency_email_3 = normalizeEmail($_POST['emergency_email_3'] ?? '');
$health_card = isset($health_card) ? $health_card : null;
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
    
    // Weight validation for users (must be at least 45 kg)
    if ($weight === null || $weight < 45) {
        redirectWithError("Minimum weight requirement is 45 kg to be eligible as a donor.");
    }

    // Emergency contact emails: required, valid, and unique
    $emergencyEmails = [$emergency_email_1, $emergency_email_2, $emergency_email_3];
    foreach ($emergencyEmails as $emergencyEmail) {
        if ($emergencyEmail === '' || !isValidEmail($emergencyEmail)) {
            redirectWithError("Please provide 3 valid emergency contact emails.");
        }
    }

    if (count(array_unique($emergencyEmails)) !== 3) {
        redirectWithError("Emergency contact emails must be different from each other.");
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
    $emergency_email_1 = '';
    $emergency_email_2 = '';
    $emergency_email_3 = '';
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
    'emergency_email_1' => $emergency_email_1,
    'emergency_email_2' => $emergency_email_2,
    'emergency_email_3' => $emergency_email_3,
    'health_card' => $health_card,
    'eligibility_status' => 'eligible'
];

// Generate OTP for email verification
$otp = (string)random_int(100000, 999999);
$otpHash = hash('sha256', $otp);
// Use DB-side expiry to avoid timezone mismatches; set to 20 minutes

// Delete any existing OTP for this email
$deleteOtp = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteOtp->bind_param("s", $email);
$deleteOtp->execute();

// Insert new OTP
$insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 20 MINUTE))");
$insertOtp->bind_param("ss", $email, $otpHash);

if (!$insertOtp->execute()) {
    redirectWithError("Failed to generate OTP. Please try again.");
}

$_SESSION['register_email'] = $email;

// Send OTP email
if (!sendOtpMail($email, $otp)) {
    error_log("OTP email failed for $email, but continuing with session storage");
}

regenerateCsrfToken();
header("Location: ../auth/verify-otp-register.php");
exit();
?>
