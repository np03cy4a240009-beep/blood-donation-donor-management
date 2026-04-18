<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$search = trim($_GET['search'] ?? '');
$date = trim($_GET['date'] ?? '');
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

if ($date !== '') {
    $sql .= " AND blood_inventory.collection_date = ?";
    $params[] = $date;
    $types .= 's';
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

// Total Tested
$stmt_tested = $conn->prepare("SELECT COUNT(*) as total FROM tests");
$stmt_tested->execute();
$testedCount = $stmt_tested->get_result()->fetch_assoc()['total'];
$stmt_tested->close();

// Safe Tests
$stmt_safe = $conn->prepare("SELECT COUNT(*) as total FROM tests WHERE status='safe'");
$stmt_safe->execute();
$safeCount = $stmt_safe->get_result()->fetch_assoc()['total'];
$stmt_safe->close();

// Approved Tests
$stmt_approved = $conn->prepare("SELECT COUNT(*) as total FROM tests WHERE status='approved'");
$stmt_approved->execute();
$approvedCount = $stmt_approved->get_result()->fetch_assoc()['total'];
$stmt_approved->close();

// Unsafe Tests
$stmt_unsafe = $conn->prepare("SELECT COUNT(*) as total FROM tests WHERE status='unsafe'");
$stmt_unsafe->execute();
$unsafeCount = $stmt_unsafe->get_result()->fetch_assoc()['total'];
$stmt_unsafe->close();

// Execute main query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tests_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testing - Bloodline Home</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Testing</h1>

        <!-- FILTER SECTION -->
        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="date" placeholder="Date" value="<?php echo htmlspecialchars($date); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach (getBloodGroups() as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>" <?php echo $blood === $group ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="tested" <?php echo $status === 'tested' ? 'selected' : ''; ?>>Tested</option>
                    <option value="safe" <?php echo $status === 'safe' ? 'selected' : ''; ?>>Safe</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="unsafe" <?php echo $status === 'unsafe' ? 'selected' : ''; ?>>Unsafe</option>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- TOTAL NUMBERS SECTION -->
        <h2>Total Numbers</h2>
        <div class="stats-row">
            <div class="stat-card">
                <div><h4>Tested</h4><p><?php echo (int)$testedCount; ?></p></div>
                <i class="ph-thin ph-drop" style="color:#940404;font-size:52px;"></i>
            </div>
            <div class="stat-card">
                <div><h4>Safe</h4><p><?php echo (int)$safeCount; ?></p></div>
                <i class="ph-thin ph-check" style="color:#940404;font-size:52px;"></i>
            </div>
            <div class="stat-card">
                <div><h4>Approved</h4><p><?php echo (int)$approvedCount; ?></p></div>
                <i class="ph-thin ph-flask" style="color:#940404;font-size:52px;"></i>
            </div>
            <div class="stat-card">
                <div><h4>Unsafe</h4><p><?php echo (int)$unsafeCount; ?></p></div>
                <i class="ph-thin ph-warning" style="color:#940404;font-size:52px;"></i>
            </div>
        </div>

        <!-- TEST LIST SECTION -->
        <h2 style="margin-top:30px;">Test List</h2>
        <div class="table-box">
            <?php if ($tests_result && $tests_result->num_rows > 0): ?>
                <?php while ($test = $tests_result->fetch_assoc()): ?>
                <div class="list-card" style="display:block;">
                    <!-- Top Row: UID, Status Badges, Collection Date, Blood Type, Units -->
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                        <div style="display:flex; gap:12px; align-items:center;">
                            <span style="font-weight:700; font-size:16px;">UID: <?php echo htmlspecialchars($test['unit_id'] ?? 'N/A'); ?></span>
                            <span style="background:#fbbf24; color:#333; padding:6px 14px; border-radius:20px; font-weight:700; font-size:14px;"><?php echo htmlspecialchars($test['status'] ?? 'Tested'); ?></span>
                            <span style="background:#22c55e; color:#fff; padding:6px 14px; border-radius:20px; font-weight:700; font-size:14px; display:flex; align-items:center; gap:6px;"><i class="ph-fill ph-check-circle" style="font-size:16px;"></i>Safe</span>
                        </div>
                        <div style="text-align:right;">
                            <p style="margin:0; font-weight:700;">Collection Date: <?php echo htmlspecialchars($test['collection_date'] ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Second Row: DID, Location -->
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <p style="margin:0; font-weight:600;">DID: <?php echo htmlspecialchars($test['donor_user_id'] ?? 'N/A'); ?></p>
                        <p style="margin:0; font-weight:600;">Location: <?php echo htmlspecialchars($test['city'] ?? 'N/A'); ?></p>
                    </div>

                    <!-- Third Row: Name, Contact -->
                    <div style="display:flex; justify-content:space-between; margin-bottom:16px;">
                        <p style="margin:0; font-weight:600;">Name: <?php echo htmlspecialchars($test['full_name'] ?? 'Unknown'); ?></p>
                        <p style="margin:0; font-weight:600;">Contact: <?php echo htmlspecialchars($test['phone'] ?? 'N/A'); ?></p>
                    </div>

                    <!-- Right Side Info: Blood Type & Units -->
                    <div style="position:absolute; right:30px; top:80px;">
                        <div style="text-align:right;">
                            <p style="margin:0 0 4px 0; font-size:13px; color:#666;">Blood Type</p>
                            <p style="margin:0 0 12px 0; font-weight:700; font-size:16px;"><?php echo htmlspecialchars($test['blood_type'] ?? 'N/A'); ?></p>
                            <p style="margin:0 0 4px 0; font-size:13px; color:#666;">Units</p>
                            <p style="margin:0; font-weight:700; font-size:16px;">1</p>
                        </div>
                    </div>

                    <!-- Bottom Buttons -->
                    <div style="display:flex; gap:14px; margin-top:16px;">
                        <form method="POST" action="update-test-status.php" style="flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="test_id" value="<?php echo (int)$test['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" style="width:100%; background:#22c55e; color:#fff; padding:14px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:16px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Approve</button>
                        </form>
                        <form method="POST" action="update-test-status.php" style="flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="test_id" value="<?php echo (int)$test['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" style="width:100%; background:#ef4444; color:#fff; padding:14px; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:16px; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Reject</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align:center; padding:40px;">
                    <p>No tests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>