<?php
require_once("../includes/security.php");
include("../config/db.php");

secureSessionStart();
verifyCsrf();

$email = normalizeEmail($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    exit("Email and password are required.");
}

if (!isValidEmail($email)) {
    exit("Invalid email address.");
}

$stmt = $conn->prepare("SELECT id, role, full_name, email, password FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    exit("Invalid email or password.");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    exit("Invalid email or password.");
}

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