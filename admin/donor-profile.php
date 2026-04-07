<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role='user' LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("Donor not found.");
}

$rareNote = getRareBloodGroupNote($user['blood_group'] ?? '');
$evaluation = evaluateDonorEligibility($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donor Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Profile</h1>

        <div class="profile-grid">
            <div class="profile-left">
                <h2>DID: <?php echo (int)$user['id']; ?></h2>
                <div style="font-size:90px;color:#980b0b;margin:15px 0;">[P]</div>
                <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <div style="display:flex;justify-content:center;gap:30px;margin:10px 0 20px;">
                    <span><?php echo htmlspecialchars($user['blood_group'] ?? 'N/A'); ?></span>
                    <span><?php echo htmlspecialchars($user['eligibility_status'] ?? $evaluation['status']); ?></span>
                </div>
                <hr style="margin:20px 0;">
                <div class="card" style="padding:18px;margin-bottom:20px;">
                    <h3>Total Donation</h3>
                    <h2><?php echo (int)($user['total_donation'] ?? 0); ?></h2>
                </div>
                <p style="font-size:18px;">Last donated date:<br><?php echo htmlspecialchars($user['last_donated'] ?: 'N/A'); ?></p>
                <br>
                <p style="font-size:18px;">Next Eligible date:<br><?php echo htmlspecialchars($user['next_eligible_date'] ?: ($evaluation['next_eligible_date'] ?: 'N/A')); ?></p>

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
                    <h2>Contact Information:</h2>
                    <p style="margin-top:10px;font-size:18px;">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="font-size:18px;">Address: <?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></p>
                    <p style="font-size:18px;">City: <?php echo htmlspecialchars($user['city'] ?? 'N/A'); ?></p>
                    <p style="font-size:18px;">Phone: <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
                </div>

                <div class="box">
                    <h2>Physical Information:</h2>
                    <div class="circle-meta">
                        <div class="item"><span><?php echo htmlspecialchars($user['age'] ?? '0'); ?></span><p>Age</p></div>
                        <div class="item"><span><?php echo htmlspecialchars($user['gender'] ?? '-'); ?></span><p>Gender</p></div>
                        <div class="item"><span><?php echo htmlspecialchars($user['weight'] ?? '0'); ?></span><p>Weight</p></div>
                    </div>
                </div>

                <div class="box">
                    <h2>Medical History / Deferral Notes:</h2>
                    <p style="margin-top:10px; min-height:100px;"><?php echo nl2br(htmlspecialchars($user['medical_history'] ?? 'No medical history added.')); ?></p>
                </div>

                <div class="box">
                    <h2>Compatibility Snapshot:</h2>
                    <p style="margin-top:10px;">
                        Can Donate To:<br>
                        <?php echo htmlspecialchars(implode(', ', getCompatibleRecipientsForDonor($user['blood_group'] ?? ''))); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>