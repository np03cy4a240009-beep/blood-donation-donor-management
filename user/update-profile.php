<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit();
}

$currentUserStmt = $conn->prepare("SELECT last_donated FROM users WHERE id = ? AND role='user' LIMIT 1");
$currentUserStmt->bind_param("i", $user_id);
$currentUserStmt->execute();
$currentUser = $currentUserStmt->get_result()->fetch_assoc();

$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
$weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
$gender = trim($_POST['gender'] ?? '');
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$zip_code = trim($_POST['zip_code'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$medical_history = trim($_POST['medical_history'] ?? '');

// Handle profile image upload
$profile_image = null;
$profile_image_file = $_FILES['profile_image'] ?? null;

if ($profile_image_file && $profile_image_file['size'] > 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($profile_image_file['type'], $allowed_types)) {
        header("Location: profile.php?error=invalid_image_type");
        exit();
    }
    
    if ($profile_image_file['size'] > 5 * 1024 * 1024) { // 5MB max
        header("Location: profile.php?error=image_too_large");
        exit();
    }
    
    $upload_dir = "../uploads/profile-images/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = strtolower(pathinfo($profile_image_file['name'], PATHINFO_EXTENSION));
    $filename = "profile_" . $user_id . "_" . time() . "." . $file_ext;
    
    if (move_uploaded_file($profile_image_file['tmp_name'], $upload_dir . $filename)) {
        $profile_image = "uploads/profile-images/" . $filename;
    } else {
        header("Location: profile.php?error=upload_failed");
        exit();
    }
} else {
    // Get existing image if no new upload
    $getImageStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
    $getImageStmt->bind_param("i", $user_id);
    $getImageStmt->execute();
    $imageResult = $getImageStmt->get_result()->fetch_assoc();
    $profile_image = $imageResult['profile_image'] ?? null;
}

$evaluation = evaluateDonorEligibility([
    'age' => $age,
    'weight' => $weight,
    'gender' => $gender,
    'blood_group' => $blood_group,
    'medical_history' => $medical_history,
    'last_donated' => $currentUser['last_donated'] ?? null
]);

$eligibility_status = $evaluation['status'];
$next_eligible_date = $evaluation['next_eligible_date'];

$columnsCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'next_eligible_date'");
$hasNextEligibleDate = $columnsCheck && $columnsCheck->num_rows > 0;

if ($hasNextEligibleDate) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name=?, phone=?, age=?, weight=?, gender=?, address=?, city=?, state=?, zip_code=?, blood_group=?, medical_history=?, eligibility_status=?, next_eligible_date=?, profile_image=?
        WHERE id=? AND role='user'
    ");

    $stmt->bind_param(
        "ssidssssssssssi",
        $full_name,
        $phone,
        $age,
        $weight,
        $gender,
        $address,
        $city,
        $state,
        $zip_code,
        $blood_group,
        $medical_history,
        $eligibility_status,
        $next_eligible_date,
        $profile_image,
        $user_id
    );
} else {
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name=?, phone=?, age=?, weight=?, gender=?, address=?, city=?, state=?, zip_code=?, blood_group=?, medical_history=?, eligibility_status=?, profile_image=?
        WHERE id=? AND role='user'
    ");

    $stmt->bind_param(
        "ssidsssssssssi",
        $full_name,
        $phone,
        $age,
        $weight,
        $gender,
        $address,
        $city,
        $state,
        $zip_code,
        $blood_group,
        $medical_history,
        $eligibility_status,
        $profile_image,
        $user_id
    );
}

if ($stmt->execute()) {
    $_SESSION['full_name'] = $full_name;
    header("Location: profile.php?updated=1");
    exit();
}

header("Location: profile.php?error=update_failed");
exit();