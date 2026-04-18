<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");

// Get user donation stats
$user_id = $_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT total_donation, last_donated, eligibility_status FROM users WHERE id = ? AND role='user' LIMIT 1");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userStats = $userStmt->get_result()->fetch_assoc();

// Get donor statistics
$totalDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user'")->fetch_assoc()['total'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) total FROM blood_requests WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$urgentRequests = $conn->query("SELECT COUNT(*) total FROM blood_requests WHERE urgency='Urgent'")->fetch_assoc()['total'] ?? 0;

$recentRequests = $conn->query("SELECT * FROM blood_requests ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-dashboard-organized.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Dashboard</h1>

        <h2>Your Donation Status</h2>
        <div class="stats-row">
            <div class="stat-card">
                <div>
                    <h4>Total Donations</h4>
                    <p><?php echo (int)($userStats['total_donation'] ?? 0); ?></p>
                </div>
                <span>❤️</span>
            </div>
            <div class="stat-card">
                <div>
                    <h4>Last Donated</h4>
                    <p style="font-size:14px;">
                        <?php echo $userStats['last_donated'] ? date('M d, Y', strtotime($userStats['last_donated'])) : 'Never'; ?>
                    </p>
                </div>
                <span>📅</span>
            </div>
            <div class="stat-card">
                <div>
                    <h4>Status</h4>
                    <p style="font-size:14px;">
                        <?php 
                        $status = strtolower($userStats['eligibility_status'] ?? 'eligible');
                        if ($status === 'eligible') {
                            echo '✓ Eligible';
                        } elseif ($status === 'not eligible') {
                            echo '✗ Not Eligible';
                        } else {
                            echo ucfirst(str_replace('_', ' ', $status));
                        }
                        ?>
                    </p>
                </div>
                <span><?php echo strtolower($userStats['eligibility_status'] ?? 'eligible') === 'eligible' ? '✅' : '⚠️'; ?></span>
            </div>
            <div class="stat-card" style="cursor:pointer;transition:0.3s;" onclick="location.href='donation-history.php'">
                <div>
                    <h4>View History</h4>
                    <p style="font-size:14px;">See all donations</p>
                </div>
                <span>→</span>
            </div>
        </div>

        <h2>Total Number</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Donors</h4><p><?php echo (int)$totalDonors; ?></p></div><span>👥</span></div>
            <div class="stat-card"><div><h4>Pending requests</h4><p><?php echo (int)$pendingRequests; ?></p></div><span>📋</span></div>
            <div class="stat-card"><div><h4>Urgent</h4><p><?php echo (int)$urgentRequests; ?></p></div><span>⚠️</span></div>
        </div>
        

        <h2>Recent Blood Requests</h2>
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Hospital</th>
                        <th>Blood Type</th>
                        <th>Units</th>
                        <th>Urgency</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentRequests && $recentRequests->num_rows > 0): ?>
                        <?php while($row = $recentRequests->fetch_assoc()): ?>
                        <tr>
                            <td><strong>RID <?php echo htmlspecialchars($row['request_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['hospital_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_type']); ?></td>
                            <td><?php echo (int)$row['units']; ?></td>
                            <td>
                                <span class="<?php echo strtolower($row['urgency']) === 'urgent' ? 'badge badge-red' : 'badge badge-blue'; ?>">
                                    <?php echo htmlspecialchars($row['urgency']); ?>
                                </span>
                            </td>
                            <td><a href="appointments.php" class="btn btn-warning">Book App</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No blood requests available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>