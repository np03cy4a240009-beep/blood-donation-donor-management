<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$blood = trim($_GET['blood_group'] ?? '');
$status = trim($_GET['status'] ?? '');

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

$totalDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user'")->fetch_assoc()['total'] ?? 0;
$eligibleDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='eligible'")->fetch_assoc()['total'] ?? 0;
$deferredDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='temporarily deferred'")->fetch_assoc()['total'] ?? 0;
$notEligibleDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user' AND eligibility_status='not eligible'")->fetch_assoc()['total'] ?? 0;
$rareDonors = $conn->query("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ('A2-','A2B-','Bombay (Oh)','Rh-null')")->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donors</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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

        <h2>Total Numbers</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Donors</h4><p><?php echo (int)$totalDonors; ?></p></div><span>[D]</span></div>
            <div class="stat-card"><div><h4>Eligible</h4><p><?php echo (int)$eligibleDonors; ?></p></div><span>[OK]</span></div>
            <div class="stat-card"><div><h4>Deferred</h4><p><?php echo (int)$deferredDonors; ?></p></div><span>[~]</span></div>
            <div class="stat-card"><div><h4>Not Eligible</h4><p><?php echo (int)$notEligibleDonors; ?></p></div><span>[X]</span></div>
            <div class="stat-card"><div><h4>Rare Donors</h4><p><?php echo (int)$rareDonors; ?></p></div><span>[*]</span></div>
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