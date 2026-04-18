<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

// Build the inventory view with filters and stock counts.
$search = trim($_GET['search'] ?? '');
$date = trim($_GET['date'] ?? '');
$blood_group = trim($_GET['blood_group'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT blood_inventory.*, users.full_name
    FROM blood_inventory
    LEFT JOIN users ON blood_inventory.donor_id = users.id
    WHERE 1=1
";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (blood_inventory.unit_id LIKE ? OR blood_inventory.donor_id LIKE ? OR users.full_name LIKE ?) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($date !== '') {
    $sql .= " AND blood_inventory.collection_date = ? ";
    $params[] = $date;
    $types .= 's';
}

if ($blood_group !== '') {
    $sql .= " AND blood_inventory.blood_type = ? ";
    $params[] = $blood_group;
    $types .= 's';
}

if ($status !== '') {
    $sql .= " AND LOWER(blood_inventory.status) = LOWER(?) ";
    $params[] = $status;
    $types .= 's';
}

$sql .= " ORDER BY blood_inventory.collection_date DESC, blood_inventory.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inventory = $stmt->get_result();

$counts = [];
foreach (getBloodGroups() as $type) {
    $q = $conn->prepare("SELECT COUNT(*) total FROM blood_inventory WHERE blood_type = ? AND LOWER(status)='available'");
    $q->bind_param("s", $type);
    $q->execute();
    $counts[$type] = $q->get_result()->fetch_assoc()['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">    <script src="https://unpkg.com/@phosphor-icons/web"></script>    <script src="https://unpkg.com/phosphor-icons"></script>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn"><i class="ph-thin ph-list" style="color:#666;"></i></div></div>
        <h1 class="page-title">Inventory</h1>

        <?php if (isset($_GET['added'])): ?>
            <div class="notice-box notice-success">Inventory added successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['deducted'])): ?>
            <div class="notice-box notice-success">Inventory deducted successfully.</div>
        <?php endif; ?>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name / donor ID / unit ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">
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
                    <option value="Available" <?php if($status==='Available') echo 'selected'; ?>>Available</option>
                    <option value="expired" <?php if($status==='expired') echo 'selected'; ?>>expired</option>
                    <option value="unsafe" <?php if($status==='unsafe') echo 'selected'; ?>>unsafe</option>
                    <option value="reserved" <?php if($status==='reserved') echo 'selected'; ?>>reserved</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>

        <div style="margin-bottom:20px; display:flex; gap:10px;">
            <a href="add-inventory.php" style="background:#28a745; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">Add Inventory</a>
            <a href="../admin/deduct-inventory.php" style="background:#dc3545; color:white; padding:10px 20px; border-radius:6px; text-decoration:none; font-weight:700; display:inline-block;">Deduct Inventory</a>
        </div>

        <h2>Total Numbers</h2>
        <div class="table-box" style="margin-bottom:25px;">
            <table>
                <thead>
                    <tr>
                        <th>Blood Type</th>
                        <th>Available Units</th>
                        <th>Priority</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counts as $group => $count): ?>
                    <tr>
                        <td>
                            <span class="<?php echo getBloodGroupBadgeClass($group); ?>">
                                <?php echo htmlspecialchars($group); ?>
                            </span>
                        </td>
                        <td><?php echo (int)$count; ?></td>
                        <td><?php echo isRareBloodGroup($group) ? 'Rare priority' : 'Standard'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2>Inventory List</h2>
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Donor ID</th>
                        <th>Unit ID</th>
                        <th>Blood Type</th>
                        <th>Collection</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($inventory->num_rows > 0): ?>
                        <?php while($row = $inventory->fetch_assoc()): ?>
                        <tr>
                            <td>DID <?php echo (int)$row['donor_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['unit_id']); ?></td>
                            <td>
                                <span class="<?php echo getBloodGroupBadgeClass($row['blood_type']); ?>">
                                    <?php echo htmlspecialchars($row['blood_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['collection_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['expiry_date']); ?></td>
                            <td>
                                <span class="<?php echo getStatusBadge($row['status']); ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="inventory-view.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-light">View</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No inventory found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>