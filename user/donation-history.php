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

// Get confirmed appointments (donations)
$donationsStmt = $conn->prepare("
    SELECT id, appointment_date, appointment_time, location, status
    FROM appointments 
    WHERE user_id = ? AND status = 'confirmed'
    ORDER BY appointment_date DESC
");
$donationsStmt->bind_param("i", $user_id);
$donationsStmt->execute();
$donations = $donationsStmt->get_result();

$evaluation = evaluateDonorEligibility($user);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donation History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-profile-organized.css">
    <link rel="stylesheet" href="../assets/css/donation-history.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">My Donation History</h1>

        <a href="profile.php" class="back-button">← Back to Profile</a>

        <div class="donation-stats">
            <div class="donation-card">
                <h3>Total Donations</h3>
                <div class="big-number"><?php echo (int)($user['total_donation'] ?? 0); ?></div>
                <div class="small-text">units donated</div>
            </div>

            <div class="donation-card">
                <h3>Last Donated</h3>
                <div class="big-number" style="font-size:20px;color:#666;">
                    <?php echo $user['last_donated'] ? date('M d, Y', strtotime($user['last_donated'])) : 'Never'; ?>
                </div>
                <div class="small-text">
                    <?php 
                    if ($user['last_donated']) {
                        $days_ago = floor((time() - strtotime($user['last_donated'])) / 86400);
                        echo "($days_ago days ago)";
                    }
                    ?>
                </div>
            </div>

            <div class="donation-card">
                <h3>Next Eligible Date</h3>
                <div class="big-number" style="font-size:20px;">
                    <?php echo $user['next_eligible_date'] ? date('M d, Y', strtotime($user['next_eligible_date'])) : 'Anytime'; ?>
                </div>
                <div class="small-text">You can donate again</div>
            </div>

            <div class="donation-card">
                <h3>Current Status</h3>
                <div class="big-number" style="font-size:20px;">
                    <?php 
                    $status = strtoupper($user['eligibility_status'] ?? 'eligible');
                    if ($status == 'ELIGIBLE') {
                        echo '✓ Eligible';
                    } elseif ($status == 'NOT ELIGIBLE') {
                        echo '✗ Not Eligible';
                    } else {
                        echo ucfirst(str_replace('_', ' ', $status));
                    }
                    ?>
                </div>
                <div class="small-text">for blood donation</div>
            </div>
        </div>

        <div class="eligibility-box <?php echo strtolower($user['eligibility_status']) !== 'eligible' ? 'ineligible' : ''; ?>">
            <h4>📋 Eligibility Assessment</h4>
            <p><?php echo htmlspecialchars($evaluation['reason']); ?></p>
        </div>

        <div class="donation-history-table">
            <?php if ($donations && $donations->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Donation Date</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($donation = $donations->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($donation['appointment_date'])); ?></strong>
                                </td>
                                <td>
                                    <?php 
                                    $time = $donation['appointment_time'];
                                    $time_obj = DateTime::createFromFormat('H:i:s', $time);
                                    echo $time_obj ? $time_obj->format('h:i A') : $time;
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($donation['location']); ?></td>
                                <td>
                                    <span class="status-badge">✓ <?php echo ucfirst($donation['status']); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Donations Yet</h3>
                    <p>You haven't completed any blood donations yet.</p>
                    <p style="margin-top:20px;">
                        <a href="book-appointment.php" class="btn btn-primary" style="padding:10px 20px;text-decoration:none;">Book Your First Donation</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:40px;padding:20px;background:#f5f5f5;border-radius:10px;">
            <h3>❤️ Thank You for Donating!</h3>
            <p>Your regular blood donations save lives in our community. Each donation is vital and helps multiple patients in need.</p>
            <p style="color:#c0265d;font-weight:bold;">Keep donating responsibly! ✓</p>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>
