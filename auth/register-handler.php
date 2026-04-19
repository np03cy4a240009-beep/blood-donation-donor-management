<?php
require_once("../includes/security.php");
include("../config/db.php");

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
    redirectWithError("Email already registered.");
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$hospital_name = trim($_POST['hospital_name'] ?? '');
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
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        redirectWithError("Phone number must be exactly 10 digits. No minus sign allowed.");
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
    $hospital_name = $hospital_name !== '' ? $hospital_name : null;
}

$sql = "INSERT INTO users
(role, full_name, email, password, hospital_name, phone, age, weight, gender, address, city, province, zip_code, blood_group, medical_history, eligibility_status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssdssssssss",
    $role,
    $full_name,
    $email,
    $hashed_password,
    $hospital_name,
    $phone,
    $age,
    $weight,
    $gender,
    $address,
    $city,
    $province,
    $zip_code,
    $blood_group,
    $medical_history,
    $eligibility_status
);

if ($stmt->execute()) {
    regenerateCsrfToken();
    $_SESSION['registration_success'] = "Your email has been successfully registered!";
    header("Location: ../register.php");
    exit();
}

redirectWithError("Registration failed. Please try again.");
?>