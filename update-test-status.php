<?php
include("../config/admin-session.php");
include("../config/db.php");
require_once("../includes/security.php");

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$rejection_reason = trim($_POST['rejection_reason'] ?? '');

$allowed = ['Tested', 'Safe', 'Approved', 'Unsafe'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    exit("Invalid request.");
}

if ($status === 'Unsafe' && $rejection_reason === '') {
    exit("Rejection reason is required.");
}

$stmt = $conn->prepare("UPDATE tests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    header("Location: testing.php");
    exit();
}

exit("Failed to update test status.");
?>