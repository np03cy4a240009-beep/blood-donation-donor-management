<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$totalDonors = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='user'")->fetch_assoc()['total'] ?? 0;
$availableUnits = $conn->query("SELECT COUNT(*) as total FROM blood_inventory WHERE LOWER(status)='available'")->fetch_assoc()['total'] ?? 0;
$pendingRequests = $conn->query("SELECT COUNT(*) as total FROM blood_requests WHERE LOWER(status)='pending'")->fetch_assoc()['total'] ?? 0;
$urgentRequests = $conn->query("SELECT COUNT(*) as total FROM blood_requests WHERE LOWER(urgency)='urgent'")->fetch_assoc()['total'] ?? 0;
$rareUnits = $conn->query("
    SELECT COUNT(*) as total 
    FROM blood_inventory 
    WHERE blood_type IN ('A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null') AND LOWER(status)='available'
")->fetch_assoc()['total'] ?? 0;

$recentRequests = $conn->query("SELECT * FROM blood_requests ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Dashboard</h1>

        <div class="stats-row">
            <div class="stat-card"><div><h4>Donors</h4><p><?php echo (int)$totalDonors; ?></p></div><span>[D]</span></div>
            <div class="stat-card"><div><h4>Available Units</h4><p><?php echo (int)$availableUnits; ?></p></div><span>[B]</span></div>
            <div class="stat-card"><div><h4>Pending Requests</h4><p><?php echo (int)$pendingRequests; ?></p></div><span>[R]</span></div>
            <div class="stat-card"><div><h4>Urgent</h4><p><?php echo (int)$urgentRequests; ?></p></div><span>[!]</span></div>
            <div class="stat-card"><div><h4>Rare Units</h4><p><?php echo (int)$rareUnits; ?></p></div><span>[*]</span></div>
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
                            <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['hospital_name']); ?></td>
                            <td>
                                <span class="<?php echo getBloodGroupBadgeClass($row['blood_type']); ?>">
                                    <?php echo htmlspecialchars($row['blood_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['units']); ?></td>
                            <td>
                                <span class="<?php echo strtolower($row['urgency']) === 'urgent' ? 'badge badge-red' : 'badge badge-blue'; ?>">
                                    <?php echo htmlspecialchars($row['urgency']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo getStatusBadge($row['status']); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No recent requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>