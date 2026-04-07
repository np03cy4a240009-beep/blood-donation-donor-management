<?php
include("../config/admin-session.php");
include("../config/db.php");
require_once("../includes/security.php");

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowed = ['Tested', 'Safe', 'Approved', 'Unsafe'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    exit("Invalid request.");
}

$stmt = $conn->prepare("UPDATE tests SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    header("Location: testing.php");
    exit();
}

exit("Failed to update test status.");
?>