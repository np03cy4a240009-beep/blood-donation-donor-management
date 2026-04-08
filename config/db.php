<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$dbname = "bloodline_home";
$username = "root";
$password = "";

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    exit('Database connection failed.');
}
?>