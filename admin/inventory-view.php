<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT blood_inventory.*, users.full_name, users.phone, users.city, users.blood_group AS donor_blood_group
    FROM blood_inventory
    LEFT JOIN users ON blood_inventory.donor_id = users.id
    WHERE blood_inventory.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

// FIX BUG #5B: Replace die() with proper redirect
if (!$item) {
    header("Location: inventory.php?error=not_found");
    exit();
}

$rareNote = getRareBloodGroupNote($item['blood_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory View</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Inventory Detail</h1>

        <div class="card" style="padding:25px;max-width:900px;margin:auto;">
            <p><strong>Unit ID:</strong> <?php echo htmlspecialchars($item['unit_id']); ?></p>
            <p><strong>Donor ID:</strong> <?php echo (int)$item['donor_id']; ?></p>
            <p><strong>Donor Name:</strong> <?php echo htmlspecialchars($item['full_name'] ?? 'Unknown'); ?></p>
            <p><strong>Blood Type:</strong> <span class="<?php echo getBloodGroupBadgeClass($item['blood_type']); ?>"><?php echo htmlspecialchars($item['blood_type']); ?></span></p>
            <p><strong>Collection Date:</strong> <?php echo htmlspecialchars($item['collection_date']); ?></p>
            <p><strong>Expiry Date:</strong> <?php echo htmlspecialchars($item['expiry_date']); ?></p>
            <p><strong>Status:</strong> <span class="<?php echo getStatusBadge($item['status']); ?>"><?php echo htmlspecialchars($item['status']); ?></span></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($item['phone'] ?? 'N/A'); ?></p>
            <p><strong>City:</strong> <?php echo htmlspecialchars($item['city'] ?? 'N/A'); ?></p>

            <?php if ($rareNote !== ''): ?>
                <div class="notice-box notice-info" style="margin-top:20px;">
                    <?php echo htmlspecialchars($rareNote); ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:20px;">
                <a href="inventory.php" class="btn btn-light">Back</a>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>