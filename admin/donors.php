<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$blood = trim($_GET['blood_group'] ?? '');
$status = trim($_GET['status'] ?? '');
$viewRare = isset($_GET['view_rare']) ? (int)$_GET['view_rare'] : 0;

// If view_rare is set, filter by rare blood groups
if ($viewRare === 1) {
    $blood = ''; // Clear any other blood group filter
}
$sql = "SELECT * FROM users WHERE role='user'";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR id LIKE ? OR email LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($location !== '') {
    $sql .= " AND (city LIKE ? OR address LIKE ?)";
    $likeLoc = "%{$location}%";
    $params[] = $likeLoc;
    $params[] = $likeLoc;
    $types .= 'ss';
}

if ($blood !== '') {
    $sql .= " AND blood_group = ?";
    $params[] = $blood;
    $types .= 's';
}

if ($viewRare === 1) {
    $sql .= " AND blood_group IN ('A2-','A2B-','Bombay (Oh)','Rh-null')";
}

if ($status !== '') {
    $sql .= " AND eligibility_status = ?";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$donors = $stmt->get_result();

// Total Donors
$stmt1 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user'");
$stmt1->execute();
$totalDonors = $stmt1->get_result()->fetch_assoc()['total'] ?? 0;
$stmt1->close();

// Eligible Donors
$stmt2 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='eligible'");
$stmt2->execute();
$eligibleDonors = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;
$stmt2->close();

// Deferred Donors
$stmt3 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='temporarily deferred'");
$stmt3->execute();
$deferredDonors = $stmt3->get_result()->fetch_assoc()['total'] ?? 0;
$stmt3->close();

// Not Eligible Donors
$stmt4 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='not eligible'");
$stmt4->execute();
$notEligibleDonors = $stmt4->get_result()->fetch_assoc()['total'] ?? 0;
$stmt4->close();

// Rare Blood Donors
$stmt5 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ('A2-','A2B-','Bombay (Oh)','Rh-null')");
$stmt5->execute();
$rareDonors = $stmt5->get_result()->fetch_assoc()['total'] ?? 0;
$stmt5->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donors</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/phosphor-icons"></script>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Donors</h1>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name / email / ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="text" name="location" placeholder="location" value="<?php echo htmlspecialchars($location); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach (getBloodGroups() as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>" <?php if($blood === $group) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="eligible" <?php if($status==='eligible') echo 'selected'; ?>>eligible</option>
                    <option value="temporarily deferred" <?php if($status==='temporarily deferred') echo 'selected'; ?>>temporarily deferred</option>
                    <option value="not eligible" <?php if($status==='not eligible') echo 'selected'; ?>>not eligible</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>

        <a href="?view_rare=1" style="display:inline-block; background:#dc3545; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700; margin-bottom:20px;">View Rare Donors</a>

        <h2 style="font-weight:normal;">Total Numbers</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Donors</h4><p><?php echo (int)$totalDonors; ?></p></div><i class="ph-thin ph-users" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Eligible Donors</h4><p><?php echo (int)$eligibleDonors; ?></p></div><i class="ph-thin ph-user-circle-plus" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Donation</h4><p><?php echo (int)$deferredDonors; ?></p></div><i class="ph-thin ph-drop" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Not Eligible</h4><p><?php echo (int)$notEligibleDonors; ?></p></div><i class="ph-thin ph-user-circle-minus" style="color:#940404;font-size:52px;"></i></div>
        </div>

        <h2>Donor List</h2>
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Donor ID</th>
                        <th>Name</th>
                        <th>Blood Type</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($donors->num_rows > 0): ?>
                        <?php while($row = $donors->fetch_assoc()): ?>
                            <?php $evaluation = evaluateDonorEligibility($row); ?>
                        <tr>
                            <td>DID <?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <span class="<?php echo getBloodGroupBadgeClass($row['blood_group'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($row['blood_group'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(($row['city'] ?? '') ?: ($row['address'] ?? 'N/A')); ?></td>
                            <td>
                                <span class="<?php echo getStatusBadge($row['eligibility_status'] ?? $evaluation['status']); ?>">
                                    <?php echo htmlspecialchars($row['eligibility_status'] ?? $evaluation['status']); ?>
                                </span>
                            </td>
                            <td><?php echo isRareBloodGroup($row['blood_group'] ?? '') ? 'Rare priority' : 'Standard'; ?></td>
                            <td><a href="donor-profile.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-light">View</a></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8">No donors found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>