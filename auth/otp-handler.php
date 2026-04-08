<?php
require_once("../includes/security.php");
include("../config/db.php");
include("send-otp-mail.php");

secureSessionStart();
verifyCsrf();

$email = normalizeEmail($_POST['email'] ?? '');

if ($email === '') {
    exit("Email is required.");
}

if (!isValidEmail($email)) {
    exit("Invalid email address.");
}

$checkUser = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$checkUser->bind_param("s", $email);
$checkUser->execute();
$result = $checkUser->get_result();

if ($result->num_rows === 0) {
    exit("No account found with this email.");
}

$otp = (string)random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires_at = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$deleteOld = $conn->prepare("DELETE FROM otp_codes WHERE email = ?");
$deleteOld->bind_param("s", $email);
$deleteOld->execute();

$insertOtp = $conn->prepare("INSERT INTO otp_codes (email, otp_code, expires_at) VALUES (?, ?, ?)");
$insertOtp->bind_param("sss", $email, $otpHash, $expires_at);

if (!$insertOtp->execute()) {
    exit("Failed to generate OTP.");
}

if (sendOtpMail($email, $otp)) {
    $_SESSION['reset_email'] = $email;
    regenerateCsrfToken();
    header("Location: ../reset-password.php");
    exit();
}

exit("Failed to send OTP email.");
?>
<?php // OTP logic version 1.1 ?>