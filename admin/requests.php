<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$blood_group = trim($_GET['blood_group'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "SELECT br.*, u.full_name as requester_name FROM blood_requests br LEFT JOIN users u ON br.user_id = u.id WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (hospital_name LIKE ? OR request_id LIKE ? OR contact LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($location !== '') {
    $sql .= " AND location LIKE ?";
    $params[] = "%{$location}%";
    $types .= 's';
}

if ($blood_group !== '') {
    $sql .= " AND blood_type = ?";
    $params[] = $blood_group;
    $types .= 's';
}

if ($status !== '') {
    $sql .= " AND LOWER(status) = LOWER(?)";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result();

// Rejected Requests
$stmt_rejected = $conn->prepare("SELECT COUNT(*) total FROM blood_requests WHERE LOWER(status)='rejected'");
$stmt_rejected->execute();
$rejectedCount = $stmt_rejected->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_rejected->close();

// Pending Requests
$stmt_pending = $conn->prepare("SELECT COUNT(*) total FROM blood_requests WHERE LOWER(status)='pending'");
$stmt_pending->execute();
$pendingCount = $stmt_pending->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pending->close();

// Approved Requests
$stmt_appr = $conn->prepare("SELECT COUNT(*) total FROM blood_requests WHERE LOWER(status)='approved'");
$stmt_appr->execute();
$approvedCount = $stmt_appr->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_appr->close();

// Urgent Requests
$stmt_urgent = $conn->prepare("SELECT COUNT(*) total FROM blood_requests WHERE LOWER(urgency)='urgent'");
$stmt_urgent->execute();
$urgentCount = $stmt_urgent->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_urgent->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blood Requests</title>
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
        <h1 class="page-title">Request</h1>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="text" name="location" placeholder="location" value="<?php echo htmlspecialchars($location); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach (getBloodGroups() as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>" <?php if($blood_group === $group) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="rejected" <?php if($status==='rejected') echo 'selected'; ?>>Rejected</option>
                    <option value="pending" <?php if($status==='pending') echo 'selected'; ?>>Pending</option>
                    <option value="approved" <?php if($status==='approved') echo 'selected'; ?>>Approved</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>

        <h2 style="font-weight:normal;">Total Numbers</h2>
        <div class="stats-row" style="margin-bottom:30px;">
            <div class="stat-card"><div><h4>Rejected</h4><p><?php echo (int)$rejectedCount; ?></p></div><i class="ph-thin ph-list" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Pending</h4><p><?php echo (int)$pendingCount; ?></p></div><i class="ph-thin ph-clipboard" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Approved</h4><p><?php echo (int)$approvedCount; ?></p></div><i class="ph-thin ph-check-circle-fill" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Urgent</h4><p><?php echo (int)$urgentCount; ?></p></div><i class="ph-thin ph-warning" style="color:#940404;font-size:52px;"></i></div>
        </div>

        <h2>Request List</h2>
        <div style="display:flex;flex-direction:column;gap:20px;">
            <?php if ($requests->num_rows > 0): ?>
                <?php while($row = $requests->fetch_assoc()): ?>
                <div class="list-card" style="padding:25px;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;">
                        <div style="flex:1;">
                            <h3 style="margin:0;font-size:18px;display:inline;margin-right:15px;">RID: <?php echo (int)substr($row['request_id'], 3, 3); ?></h3>
                            <span class="<?php echo strtolower($row['urgency']) === 'urgent' ? 'badge badge-red' : 'badge badge-blue'; ?>" style="display:inline-block;margin-right:10px;">
                                <?php echo htmlspecialchars($row['urgency']); ?>
                            </span>
                            <span style="display:inline-block;background:#f5dcc8;color:#8b6f47;padding:6px 16px;border-radius:20px;font-weight:700;font-size:14px;">
                                <?php if($row['status'] === 'pending'): ?><i class="ph-thin ph-clipboard" style="margin-right:6px;font-size:16px;"></i><?php endif; ?>
                                <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
                            </span>
                        </div>
                        <div style="text-align:right;display:flex;gap:50px;align-items:center;">
                            <div>
                                <p style="margin:0;font-size:13px;color:#666;">Blood Type</p>
                                <p style="margin:3px 0 0 0;font-size:16px;font-weight:700;"><?php echo htmlspecialchars($row['blood_type']); ?></p>
                            </div>
                            <div>
                                <p style="margin:0;font-size:13px;color:#666;">Units</p>
                                <p style="margin:3px 0 0 0;font-size:16px;font-weight:700;"><?php echo (int)$row['units']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:40px;margin-bottom:20px;font-size:14px;">
                        <div>
                            <p style="margin:0;"><span style="color:#666;">Name:</span> <?php echo htmlspecialchars($row['requester_name'] ?: 'Unknown'); ?></p>
                            <p style="margin:8px 0 0 0;"><span style="color:#666;">Contact:</span> <?php echo htmlspecialchars($row['contact']); ?></p>
                            <p style="margin:8px 0 0 0;"><span style="color:#666;">Location:</span> <?php echo htmlspecialchars($row['location']); ?></p>
                        </div>
                        <div>
                            <p style="margin:0;"><span style="color:#666;">Request Date:</span> <?php echo htmlspecialchars($row['request_date']); ?></p>
                            <p style="margin:8px 0 0 0;"><span style="color:#666;">Required By:</span> <?php echo htmlspecialchars($row['required_by']); ?></p>
                        </div>
                    </div>

                    <div style="display:flex;gap:15px;margin-bottom:15px;">
                        <form method="POST" action="update-request-status.php" style="flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" style="background:#28a745;color:white;padding:12px 0;border:none;border-radius:6px;cursor:pointer;font-weight:700;width:100%;">Approve</button>
                        </form>
                        <form method="POST" action="update-request-status.php" style="flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" style="background:#dc3545;color:white;padding:12px 0;border:none;border-radius:6px;cursor:pointer;font-weight:700;width:100%;">Reject</button>
                        </form>
                        <form method="POST" action="request-donor.php" style="flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" style="flex:1;background:#d89366;color:white;padding:12px 0;border:none;border-radius:6px;cursor:pointer;font-weight:700;text-align:center;width:100%;">Request Donor</button>
                        </form>
                    </div>

                    <?php if (isset($_GET['action'])): ?>
                        <?php if ($_GET['action'] === 'approved'): ?>
                            <div class="notice-box notice-success">Request approved successfully.</div>
                        <?php elseif ($_GET['action'] === 'rejected'): ?>
                            <div class="notice-box notice-success">Request rejected successfully.</div>
                        <?php elseif ($_GET['action'] === 'donor_requested'): ?>
                            <div class="notice-box notice-success">Donor request sent successfully.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="list-card" style="text-align:center;padding:40px;">No requests found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>