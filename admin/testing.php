<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$blood = trim($_GET['blood_group'] ?? '');

$sql = "
    SELECT tests.*, blood_inventory.unit_id, blood_inventory.blood_type, blood_inventory.collection_date,
           users.id AS donor_user_id, users.full_name, users.phone, users.city
    FROM tests
    LEFT JOIN blood_inventory ON tests.inventory_id = blood_inventory.id
    LEFT JOIN users ON blood_inventory.donor_id = users.id
    WHERE 1=1
";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (blood_inventory.unit_id LIKE ? OR users.full_name LIKE ? OR users.id LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

if ($status !== '') {
    $sql .= " AND tests.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($blood !== '') {
    $sql .= " AND blood_inventory.blood_type = ?";
    $params[] = $blood;
    $types .= 's';
}

$sql .= " ORDER BY tests.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tests = $stmt->get_result();

// FIX BUG: Convert to prepared statements for consistency
$stmtTested = $conn->prepare("SELECT COUNT(*) total FROM tests WHERE status='Tested'");
$stmtTested->execute();
$tested = $stmtTested->get_result()->fetch_assoc()['total'] ?? 0;

$stmtSafe = $conn->prepare("SELECT COUNT(*) total FROM tests WHERE status='Safe'");
$stmtSafe->execute();
$safe = $stmtSafe->get_result()->fetch_assoc()['total'] ?? 0;

$stmtApproved = $conn->prepare("SELECT COUNT(*) total FROM tests WHERE status='Approved'");
$stmtApproved->execute();
$approved = $stmtApproved->get_result()->fetch_assoc()['total'] ?? 0;

$stmtUnsafe = $conn->prepare("SELECT COUNT(*) total FROM tests WHERE status='Unsafe'");
$stmtUnsafe->execute();
$unsafe = $stmtUnsafe->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Testing</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Testing</h1>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name / donor ID / unit ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="text" value="" placeholder="Use search and filters" disabled>
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
                    <option value="Tested" <?php if($status==='Tested') echo 'selected'; ?>>Tested</option>
                    <option value="Safe" <?php if($status==='Safe') echo 'selected'; ?>>Safe</option>
                    <option value="Approved" <?php if($status==='Approved') echo 'selected'; ?>>Approved</option>
                    <option value="Unsafe" <?php if($status==='Unsafe') echo 'selected'; ?>>Unsafe</option>
                </select>
                <button type="submit">Apply</button>
            </form>
        </div>

        <h2>Total Numbers</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Tested</h4><p><?php echo (int)$tested; ?></p></div><span>[T]</span></div>
            <div class="stat-card"><div><h4>Safe</h4><p><?php echo (int)$safe; ?></p></div><span>[+]</span></div>
            <div class="stat-card"><div><h4>Approved</h4><p><?php echo (int)$approved; ?></p></div><span>[OK]</span></div>
            <div class="stat-card"><div><h4>Unsafe</h4><p><?php echo (int)$unsafe; ?></p></div><span>[-]</span></div>
        </div>

        <h2>Test List</h2>

        <?php if ($tests->num_rows > 0): ?>
            <?php while($row = $tests->fetch_assoc()): ?>
            <div class="list-card">
                <div style="display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap;">
                    <div>
                        <p>
                            <strong>UID: <?php echo htmlspecialchars($row['unit_id'] ?? 'N/A'); ?></strong>
                            <span class="<?php echo getStatusBadge($row['status']); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </p>
                        <br>
                        <p>DID: <?php echo htmlspecialchars($row['donor_user_id'] ?? 'N/A'); ?></p>
                        <p>Name: <?php echo htmlspecialchars($row['full_name'] ?? 'Unknown'); ?></p>
                    </div>
                    <div>
                        <p>Collection Date: <?php echo htmlspecialchars($row['collection_date'] ?? 'N/A'); ?></p>
                        <p>Location: <?php echo htmlspecialchars($row['city'] ?? 'N/A'); ?></p>
                        <p>Contact: <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></p>
                        <p>Blood Type: 
                            <span class="<?php echo getBloodGroupBadgeClass($row['blood_type'] ?? ''); ?>">
                                <?php echo htmlspecialchars($row['blood_type'] ?? 'N/A'); ?>
                            </span>
                        </p>
                    </div>
                </div>

                <?php if (isRareBloodGroup($row['blood_type'] ?? '')): ?>
                    <div class="notice-box notice-info">
                        <?php echo htmlspecialchars(getRareBloodGroupNote($row['blood_type'])); ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex;gap:20px;margin-top:20px;flex-wrap:wrap;">

                    <?php if ($row['status'] !== 'Safe'): ?>
                        <form method="POST" action="update-test-status.php" style="display:inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="Safe">
                            <button class="btn" style="background:#3f9800;color:#fff;flex:1;text-align:center;" type="submit">Mark Safe</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($row['status'] !== 'Unsafe'): ?>
                        <form method="POST" action="update-test-status.php" style="display:inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="Unsafe">
                            <button class="btn" style="background:#ff4040;color:#fff;flex:1;text-align:center;" type="submit">Mark Unsafe</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($row['status'] !== 'Approved'): ?>
                        <form method="POST" action="update-test-status.php" style="display:inline;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="Approved">
                            <button class="btn" style="background:#d89258;color:#fff;flex:1;text-align:center;" type="submit">Approve</button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="card" style="padding:18px;">No tests found.</div>
        <?php endif; ?>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>