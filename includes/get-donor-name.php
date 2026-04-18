<?php
include("../config/db.php");

$donor_id = (int)($_GET['donor_id'] ?? 0);

if ($donor_id > 0) {
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $donor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo htmlspecialchars($row['full_name']);
    } else {
        echo "Unknown";
    }
} else {
    echo "Unknown";
}
?>
