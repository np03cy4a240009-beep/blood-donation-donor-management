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

// FIX BUG #4B: Convert to prepared statements for consistency
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
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" placeholder="Date">
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
                <button type="submit" class="btn-filter-apply">Apply</button>
                <a href="testing.php" class="btn-filter-clear">Clear</a>
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
        <div class="tests-container">
        <?php if ($tests->num_rows > 0): ?>
            <?php while($row = $tests->fetch_assoc()): ?>
            <div class="test-card">
                <div class="test-header">
                    <div class="test-id">UID: <?php echo htmlspecialchars($row['unit_id'] ?? 'N/A'); ?></div>
                    <div class="test-badges">
                        <span class="<?php echo getStatusBadge($row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="test-body">
                    <div class="test-info">
                        <p><strong>DID:</strong> <?php echo htmlspecialchars($row['donor_user_id'] ?? 'N/A'); ?></p>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($row['full_name'] ?? 'Unknown'); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($row['city'] ?? 'N/A'); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="test-dates">
                        <p><strong>Collection Date:</strong> <?php echo htmlspecialchars($row['collection_date'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="test-blood">
                        <p><strong>Blood Type:</strong> <span class="<?php echo getBloodGroupBadgeClass($row['blood_type'] ?? ''); ?>"><?php echo htmlspecialchars($row['blood_type'] ?? 'N/A'); ?></span></p>
                        <p><strong>Units:</strong> 1</p>
                    </div>
                </div>

                <?php if (isRareBloodGroup($row['blood_type'] ?? '')): ?>
                    <div class="notice-box notice-info">
                        <?php echo htmlspecialchars(getRareBloodGroupNote($row['blood_type'])); ?>
                    </div>
                <?php endif; ?>

                <div class="test-actions">
                    <?php if ($row['status'] !== 'Approved'): ?>
                        <form method="POST" action="update-test-status.php" style="display:inline;flex:1;">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="status" value="Approved">
                            <button class="btn btn-success" type="submit" style="width:100%;">Approve</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($row['status'] !== 'Unsafe'): ?>
                        <button type="button" class="btn btn-danger" style="width:100%;" onclick="openRejectModal(<?php echo (int)$row['id']; ?>)">Reject</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="test-card" style="text-align:center;">
                <p style="color:#999;">No tests found.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>State your reason to reject:</h2>
            <button class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form id="rejectForm" method="POST" action="update-test-status.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="rejectId">
            <input type="hidden" name="status" value="Unsafe">
            <textarea id="rejectReason" name="rejection_reason" class="modal-textarea" placeholder="Enter reason for rejection..." required></textarea>
            <div class="modal-actions">
                <button type="submit" class="btn btn-success">Submit</button>
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/dashboard.js"></script>
<script>
let currentRejectId = null;

function openRejectModal(testId) {
    currentRejectId = testId;
    document.getElementById('rejectId').value = testId;
    const modal = document.getElementById('rejectModal');
    modal.style.display = 'flex';
    document.getElementById('rejectReason').value = '';
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('rejectModal');
    if (event.target == modal) {
        closeRejectModal();
    }
});
</script>
</body>
</html>