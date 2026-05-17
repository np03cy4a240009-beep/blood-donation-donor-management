<?php
include("../config/admin-session.php");
include("../config/db.php");
require_once("../includes/security.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfCheck()) {
    header("Location: profile.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$full_name = trim($_POST['full_name'] ?? '');
$hospital_name = trim($_POST['hospital_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');

if ($full_name === '') {
    header("Location: profile.php?error=name");
    exit();
}

if ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
    header("Location: profile.php?error=phone");
    exit();
}

$stmt = $conn->prepare("
    UPDATE users
    SET full_name = ?, hospital_name = ?, phone = ?, address = ?, city = ?, province = ?
    WHERE id = ? AND role = 'admin'
");
$stmt->bind_param("ssssssi", $full_name, $hospital_name, $phone, $address, $city, $province, $user_id);
$stmt->execute();
$stmt->close();

$_SESSION['user_full_name'] = $full_name;
$_SESSION['hospital_name'] = $hospital_name;

header("Location: profile.php?updated=1");
exit();
