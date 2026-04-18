<?php
include("../config/user-session.php");
include("../config/db.php");
require_once("../includes/security.php");

secureSessionStart();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        header("Location: profile.php?error=password_mismatch");
        exit();
    }

    if (!strongPassword($new_password)) {
        header("Location: profile.php?error=weak_password");
        exit();
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($current_password, $user['password'])) {
        header("Location: profile.php?error=wrong_password");
        exit();
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->bind_param("si", $hashed, $user_id);

    if ($update->execute()) {
        regenerateCsrfToken();
        header("Location: profile.php?password_changed=1");
        exit();
    }

    header("Location: profile.php?error=update_failed");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="padding:40px;background:#f8eef1;">
    <div class="card" style="max-width:500px;margin:auto;padding:30px;">
        <h2 style="margin-bottom:20px;">Change Password</h2>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="password" name="current_password" placeholder="Current Password" required style="width:100%;height:42px;margin-bottom:12px;">
            <input type="password" name="new_password" placeholder="New Password" required style="width:100%;height:42px;margin-bottom:12px;">
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required style="width:100%;height:42px;margin-bottom:12px;">
            <button class="btn btn-primary" type="submit">Change Password</button>
            <a href="profile.php" class="btn btn-light">Back</a>
        </form>
    </div>
</body>
</html>