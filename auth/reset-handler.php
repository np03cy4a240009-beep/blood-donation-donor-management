<?php
require_once("../includes/security.php");
include("../config/db.php");

secureSessionStart();
verifyCsrf();

if (!isset($_SESSION['reset_email'])) {
    exit("Session expired. Please request OTP again.");
}

$email = normalizeEmail($_SESSION['reset_email']);
$otp = trim($_POST['otp'] ?? '');
$new_password = $_POST['new_password'] ?? '';

if ($otp === '' || $new_password === '') {
    exit("All fields are required.");
}

if (!strongPassword($new_password)) {
    exit("Password must be at least 8 characters long.");
}

$sql = "SELECT * FROM otp_codes WHERE email = ? AND expires_at >= NOW() ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    exit("Invalid or expired OTP.");
}

$row = $result->fetch_assoc();

if (!password_verify($otp, $row['otp_code'])) {
    exit("Invalid or expired OTP.");
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

    header("Location: ../login.php");
    exit();
}

exit("Password update failed.");
?>