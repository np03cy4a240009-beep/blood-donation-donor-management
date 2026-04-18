<?php
include("../config/user-session.php");
include("../config/db.php");
require_once("../includes/security.php");

verifyCsrf();

$id = (int)($_POST['cancel'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if ($id <= 0) {
    exit("Invalid request.");
}

$stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header("Location: appointments.php");
exit();
?>