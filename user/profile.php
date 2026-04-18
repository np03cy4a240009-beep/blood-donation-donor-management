<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role='user' LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

$rareNote = getRareBloodGroupNote($user['blood_group'] ?? '');
$evaluation = evaluateDonorEligibility($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-profile-organized.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Profile</h1>

        <?php if (isset($_GET['updated'])): ?>
            <div class="notice-box notice-success">✓ Profile updated successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['password_changed'])): ?>
            <div class="notice-box notice-success">✓ Password changed successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): 
            $errors = [
                'invalid_image_type' => '✗ Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.',
                'image_too_large' => '✗ Image is too large. Maximum size is 5MB.',
                'upload_failed' => '✗ Failed to upload image. Please try again.',
                'update_failed' => '✗ Failed to update profile. Please try again.',
                'password_mismatch' => '✗ New passwords do not match.',
                'weak_password' => '✗ Password must be at least 8 characters long.',
                'wrong_password' => '✗ Current password is incorrect.'
            ];
            $error_msg = $errors[$_GET['error']] ?? '✗ An error occurred. Please try again.';
        ?>
            <div class="notice-box notice-error"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="update-profile.php" method="POST" enctype="multipart/form-data">
            <div class="profile-grid">
                <div class="profile-left">
                    <h2>DID: <?php echo $user['id']; ?></h2>
                    <div class="profile-avatar">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>

                    <div style="display:flex;justify-content:center;gap:30px;margin:10px 0 20px;">
                        <span><?php echo htmlspecialchars($user['blood_group'] ?? 'N/A'); ?></span>
                        <span><?php echo htmlspecialchars($user['eligibility_status'] ?? 'eligible'); ?></span>
                    </div>

                    <hr style="margin:20px 0;">

                    <div class="card" style="padding:18px;margin-bottom:20px;">
                        <h3>Total Donation</h3>
                        <h2><?php echo (int)($user['total_donation'] ?? 0); ?></h2>
                    </div>

                    <p style="font-size:18px;">Last donated date:<br><?php echo $user['last_donated'] ?: 'N/A'; ?></p>
                    <br>
                    <p style="font-size:18px;">Next Eligible date:<br><?php echo $user['next_eligible_date'] ?: ($evaluation['next_eligible_date'] ?: 'N/A'); ?></p>
                    <br>
                    <div style="display:flex;gap:10px;flex-direction:column;">
                        <a href="change-password.php" class="btn btn-light">Change Password</a>
                        <a href="donation-history.php" class="btn btn-primary" style="text-align:center;text-decoration:none;">View Donation History</a>
                    </div>

                    <div style="margin-top:20px;padding:18px;background:#fff7f8;border-radius:10px;text-align:center;">
                        <label style="display:block;font-weight:700;margin-bottom:10px;">Change Profile Picture:</label>
                        <input type="file" name="profile_image" accept="image/*" style="margin-top:8px;cursor:pointer;">
                        <p style="font-size:12px;color:#666;margin-top:8px;">Max 5MB • JPEG, PNG, GIF, WebP</p>
                    </div>

                    <div class="notice-box notice-info" style="margin-top:20px;text-align:left;">
                        <strong>Eligibility Review:</strong><br>
                        <?php echo htmlspecialchars($evaluation['reason']); ?>
                    </div>

                    <?php if ($rareNote !== ''): ?>
                        <div class="notice-box notice-info" style="margin-top:20px;">
                            <?php echo htmlspecialchars($rareNote); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-right">
                    <div class="box">
                        <h2>Contact Information</h2>
                        <p style="margin-bottom:10px;">Email: <?php echo htmlspecialchars($user['email']); ?></p>

                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Phone" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Address" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="City" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="text" name="state" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>" placeholder="State" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="text" name="zip_code" value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>" placeholder="Zip Code" style="width:100%;height:40px;">
                    </div>

                    <div class="box">
                        <h2>Physical Information</h2>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>" placeholder="Age" style="width:100%;height:40px;margin-bottom:10px;">
                        <input type="number" step="0.01" name="weight" value="<?php echo htmlspecialchars($user['weight'] ?? ''); ?>" placeholder="Weight" style="width:100%;height:40px;margin-bottom:10px;">
                        <select name="gender" style="width:100%;height:40px;margin-bottom:10px;">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php if(($user['gender'] ?? '') === 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if(($user['gender'] ?? '') === 'Female') echo 'selected'; ?>>Female</option>
                            <option value="Other" <?php if(($user['gender'] ?? '') === 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                        <select name="blood_group" style="width:100%;height:40px;">
                            <option value="">Select Blood Group</option>
                            <?php foreach (getBloodGroups() as $group): ?>
                                <option value="<?php echo htmlspecialchars($group); ?>" <?php if(($user['blood_group'] ?? '') === $group) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($group); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="box">
                        <h2>Medical History / Deferral Notes</h2>
                        <textarea name="medical_history" style="width:100%;min-height:120px;"><?php echo htmlspecialchars($user['medical_history'] ?? ''); ?></textarea>
                        <p style="margin-top:10px;font-size:14px;color:#555;">
                            Add conditions like pregnancy, breastfeeding, recent surgery, tattoo, infection, antibiotics, malaria history, transfusion, hepatitis, HIV, etc.
                        </p>
                    </div>

                    <button class="btn btn-primary" type="submit" style="width:100%;">Update Profile</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>