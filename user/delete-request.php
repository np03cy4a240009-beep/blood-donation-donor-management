<?php
include("../config/user-session.php");
include("../config/db.php");
require_once("../includes/security.php");

verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    exit("Invalid request.");
}

$stmt = $conn->prepare("DELETE FROM blood_requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute()) {
    header("Location: requests.php");
    exit();
}

exit("Failed to delete request.");
?>