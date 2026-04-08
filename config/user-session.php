<?php
require_once(__DIR__ . "/../includes/security.php");

secureSessionStart();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header("Location: ../login.php");
    exit();
}
?>