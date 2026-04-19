<?php
require_once("../includes/security.php");
include("../config/db.php");

secureSessionStart();
verifyCsrf();

$email = normalizeEmail($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$hospital_name = trim($_POST['hospital_name'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = "Email and password are required.";
    header("Location: ../login.php");
    exit();
}

if (!isValidEmail($email)) {
    $_SESSION['login_error'] = "Invalid email address. Please enter a valid email.";
    header("Location: ../login.php");
    exit();
}

$stmt = $conn->prepare("SELECT id, role, full_name, email, password FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['login_error'] = "Invalid email or password. Please try again.";
    header("Location: ../login.php");
    exit();
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = "Invalid email or password. Please try again.";
    header("Location: ../login.php");
    exit();
}

session_regenerate_id(true);
regenerateCsrfToken();

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['hospital_name'] = $hospital_name;

if ($user['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit();
}

header("Location: ../user/dashboard.php");
exit();
?>