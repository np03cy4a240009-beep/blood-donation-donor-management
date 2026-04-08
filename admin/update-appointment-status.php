<?php
include("../config/admin-session.php");
include("../config/db.php");
require_once("../includes/security.php");
include("../includes/eligibility.php");

requirePost();
verifyCsrf();

$id = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');

$allowed = ['pending', 'confirmed', 'cancelled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    exit("Invalid request.");
}

// Get appointment details including user_id
$appointmentStmt = $conn->prepare("
    SELECT appointments.*, users.blood_group, users.last_donated, users.total_donation 
    FROM appointments 
    JOIN users ON appointments.user_id = users.id
    WHERE appointments.id = ? 
    LIMIT 1
");
$appointmentStmt->bind_param("i", $id);
$appointmentStmt->execute();
$appointment = $appointmentStmt->get_result()->fetch_assoc();

if (!$appointment) {
    exit("Appointment not found.");
}

$user_id = $appointment['user_id'];

// Update appointment status
$statusStmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$statusStmt->bind_param("si", $status, $id);

if (!$statusStmt->execute()) {
    exit("Failed to update appointment.");
}

// IF CONFIRMED, RECORD THE DONATION
if ($status === 'confirmed') {
    $today = date('Y-m-d');
    $new_total = ($appointment['total_donation'] ?? 0) + 1;
    
    // Fetch user data for eligibility recalculation
    $userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role='user' LIMIT 1");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $user = $userStmt->get_result()->fetch_assoc();
    
    if ($user) {
        // Recalculate eligibility after donation
        $eval = evaluateDonorEligibility([
            'age' => $user['age'],
            'weight' => $user['weight'],
            'gender' => $user['gender'],
            'blood_group' => $user['blood_group'],
            'medical_history' => $user['medical_history'],
            'last_donated' => $today  // Set to today since they just donated
        ]);
        
        $new_eligibility = $eval['status'];
        $next_eligible_date = $eval['next_eligible_date'] ?? null;
        
        // Check if next_eligible_date column exists
        $columnsCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'next_eligible_date'");
        $hasNextEligibleDate = $columnsCheck && $columnsCheck->num_rows > 0;
        
        // Update user donation tracking and eligibility
        if ($hasNextEligibleDate) {
            $updateDonation = $conn->prepare("
                UPDATE users 
                SET last_donated = ?, total_donation = ?, eligibility_status = ?, next_eligible_date = ?
                WHERE id = ? AND role = 'user'
            ");
            $updateDonation->bind_param("sissi", $today, $new_total, $new_eligibility, $next_eligible_date, $user_id);
        } else {
            $updateDonation = $conn->prepare("
                UPDATE users 
                SET last_donated = ?, total_donation = ?, eligibility_status = ?
                WHERE id = ? AND role = 'user'
            ");
            $updateDonation->bind_param("sisi", $today, $new_total, $new_eligibility, $user_id);
        }
        
        if (!$updateDonation->execute()) {
            error_log("Warning: Failed to update donation tracking for user $user_id");
        }
    }
}

regenerateCsrfToken();
header("Location: appointments.php?updated=1");
exit();
?>