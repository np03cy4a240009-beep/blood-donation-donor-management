<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

// Total Donors
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role='user'");
$stmt->execute();
$totalDonors = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Available Units
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM blood_inventory WHERE LOWER(status)='available'");
$stmt->execute();
$availableUnits = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Pending Requests
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM blood_requests WHERE LOWER(status)='pending'");
$stmt->execute();
$pendingRequests = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Urgent Requests
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM blood_requests WHERE LOWER(urgency)='urgent'");
$stmt->execute();
$urgentRequests = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Rare Units
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM blood_inventory 
    WHERE blood_type IN ('A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null') AND LOWER(status)='available'
");
$stmt->execute();
$rareUnits = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Recent Requests
$stmt = $conn->prepare("SELECT * FROM blood_requests ORDER BY id DESC LIMIT 5");
$stmt->execute();
$recentRequests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://unpkg.com/phosphor-icons"></script>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Dashboard</h1>

        <h2>Total Number</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Donors</h4><p><?php echo (int)$totalDonors; ?></p></div><i class="ph-thin ph-users" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Available blood units</h4><p><?php echo (int)$availableUnits; ?></p></div><i class="ph-thin ph-drop" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Pending requests</h4><p><?php echo (int)$pendingRequests; ?></p></div><i class="ph-thin ph-clipboard" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Urgent</h4><p><?php echo (int)$urgentRequests; ?></p></div><i class="ph-thin ph-warning" style="color:#940404;font-size:52px;"></i></div>
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
                            <td><?php echo htmlspecialchars(preg_replace('/^RID/', 'RID ', $row['request_id'])); ?></td>
                            <td>Bir Hospital</td>
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