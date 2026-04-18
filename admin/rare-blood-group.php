<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");

// Show and filter donors who belong to monitored rare blood groups.
$search = trim($_GET['search'] ?? '');
$location = trim($_GET['location'] ?? '');
$blood = trim($_GET['blood_group'] ?? '');
$status = trim($_GET['status'] ?? '');

// Define rare blood groups
$rareBloodGroups = ['A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null'];

// Build placeholders for IN clause
$placeholders = implode(',', array_fill(0, count($rareBloodGroups), '?'));
$sql = "SELECT * FROM users WHERE role='user' AND blood_group IN ($placeholders)";

$params = $rareBloodGroups;  // Start with rare blood groups
$types = str_repeat('s', count($rareBloodGroups));

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
$rareDonors = $stmt->get_result();

// Get rare donor counts by status
$rareGroupList = ['A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null'];
$rarePlaceholders = implode(',', array_fill(0, 4, '?'));

$stmtRareEligible = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ($rarePlaceholders) AND eligibility_status='eligible'");
$stmtRareEligible->bind_param("ssss", ...$rareGroupList);
$stmtRareEligible->execute();
$rareEligible = $stmtRareEligible->get_result()->fetch_assoc()['total'] ?? 0;

$stmtRareNotEligible = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ($rarePlaceholders) AND eligibility_status='not eligible'");
$stmtRareNotEligible->bind_param("ssss", ...$rareGroupList);
$stmtRareNotEligible->execute();
$rareNotEligible = $stmtRareNotEligible->get_result()->fetch_assoc()['total'] ?? 0;

$stmtRareDeferred = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ($rarePlaceholders) AND eligibility_status='temporarily deferred'");
$stmtRareDeferred->bind_param("ssss", ...$rareGroupList);
$stmtRareDeferred->execute();
$rareDeferred = $stmtRareDeferred->get_result()->fetch_assoc()['total'] ?? 0;

$totalRare = ($rareEligible + $rareNotEligible + $rareDeferred);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rare Blood Group</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Rare Blood Group</h1>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="text" name="location" placeholder="location" value="<?php echo htmlspecialchars($location); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach ($rareBloodGroups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>" <?php if($blood === $group) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="eligible" <?php if($status === 'eligible') echo 'selected'; ?>>Eligible</option>
                    <option value="temporarily deferred" <?php if($status === 'temporarily deferred') echo 'selected'; ?>>Temporarily Deferred</option>
                    <option value="not eligible" <?php if($status === 'not eligible') echo 'selected'; ?>>Not Eligible</option>
                </select>
                <button type="submit">Apply</button>
                <a href="rare-blood-group.php" class="btn-clear">Clear</a>
            </form>
        </div>

        <h2>Rare Donor List</h2>
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
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rareDonors->num_rows > 0): ?>
                        <?php while($row = $rareDonors->fetch_assoc()): ?>
                        <tr>
                            <td>DID <?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <span class="<?php echo getBloodGroupBadgeClass($row['blood_group']); ?>">
                                    <?php echo htmlspecialchars($row['blood_group']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['city'] ?? 'N/A'); ?></td>
                            <td><?php echo getStatusBadge($row['eligibility_status']); ?></td>
                            <td class="table-action-btn">
                                <a href="donor-profile.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-warning">View</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;">No rare blood group donors found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include("../includes/footer.php"); ?>
<script src="../assets/js/main.js"></script>
</body>
</html>
