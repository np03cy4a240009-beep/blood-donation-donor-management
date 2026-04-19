<?php
require_once(__DIR__ . "/../includes/security.php");

secureSessionStart();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>