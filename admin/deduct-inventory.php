<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_id = trim($_POST['unit_id'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($unit_id === '' || $reason === '') {
        $error = "All fields are required.";
    } else {
        $checkUnit = $conn->prepare("SELECT id, status FROM blood_inventory WHERE unit_id = ? LIMIT 1");
        $checkUnit->bind_param("s", $unit_id);
        $checkUnit->execute();
        $unitResult = $checkUnit->get_result();

        if ($unitResult->num_rows === 0) {
            $error = "Unit ID not found.";
        } else {
            $unit = $unitResult->fetch_assoc();
            
            $newStatus = ($reason === 'expired') ? 'expired' : 'unsafe';
            
            $stmt = $conn->prepare("UPDATE blood_inventory SET status = ? WHERE unit_id = ?");
            $stmt->bind_param("ss", $newStatus, $unit_id);

            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Failed to deduct inventory.";
            }
        }
    }
}

$stmt = $conn->prepare("SELECT id, unit_id, blood_type, collection_date, expiry_date, donor_id FROM blood_inventory WHERE LOWER(status)='available' ORDER BY unit_id DESC");
$stmt->execute();
$inventory = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deduct Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <h1 class="page-title">Deduct Inventory</h1>

        <?php if ($error): ?>
            <div class="notice-box notice-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="successMessage" style="position:fixed;bottom:40px;right:40px;background:white;padding:20px 30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:15px;z-index:1000;font-weight:600;">
                <span>Deducted Successfully</span>
                <button onclick="closeSuccessMessage()" style="background:none;border:none;cursor:pointer;font-size:20px;color:#666;">✕</button>
            </div>
            <script>
                function closeSuccessMessage() {
                    document.getElementById('successMessage').style.display = 'none';
                }
                setTimeout(function() {
                    window.location.href = 'inventory.php?deducted=1';
                }, 2000);
            </script>
        <?php endif; ?>

        <div class="card" style="padding:40px;max-width:900px;margin:auto;">
            <form method="POST" id="deductForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:24px;">
                    <!-- Left Column -->
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:8px;">Requester:</label>
                        <input type="text" id="requester" readonly style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;background-color:#f9f9f9;">

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Blood Type:</label>
                        <input type="text" id="bloodType" readonly style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;background-color:#f9f9f9;">

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Collection Date:</label>
                        <input type="text" id="collectionDate" readonly style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;background-color:#f9f9f9;">
                    </div>

                    <!-- Right Column -->
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:8px;">Unit ID:</label>
                        <select name="unit_id" id="unitIdSelect" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" onchange="updateUnitInfo()">
                            <option value="">Select Unit</option>
                            <?php if ($inventory): ?>
                                <?php while($unit = $inventory->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($unit['unit_id']); ?>" 
                                            data-blood-type="<?php echo htmlspecialchars($unit['blood_type']); ?>"
                                            data-collection-date="<?php echo htmlspecialchars($unit['collection_date']); ?>"
                                            data-expiry-date="<?php echo htmlspecialchars($unit['expiry_date']); ?>"
                                            data-donor-id="<?php echo (int)$unit['donor_id']; ?>">
                                        <?php echo htmlspecialchars($unit['unit_id']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Unit:</label>
                        <input type="text" id="unit" value="1" readonly style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;background-color:#f9f9f9;">

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Expiry Date:</label>
                        <input type="text" id="expiryDate" readonly style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;background-color:#f9f9f9;">
                    </div>
                </div>

                <!-- Full Width Special Note -->
                <label style="display:block;font-weight:600;margin-bottom:8px;">Special note:</label>
                <textarea id="specialNote" readonly style="width:100%;min-height:120px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:inherit;background-color:#f9f9f9;"></textarea>

                <button class="btn" type="submit" style="width:100%;background:#dc3545;color:white;padding:14px;border:none;border-radius:6px;cursor:pointer;font-weight:700;font-size:16px;margin-bottom:12px;">Deduct Inventory</button>
                <a href="inventory.php" class="btn btn-light" style="display:block;text-align:center;padding:14px;border-radius:6px;text-decoration:none;background:#f0f0f0;color:#333;">Back</a>
            </form>
        </div>
    </div>
</div>

<script>
function updateUnitInfo() {
    const select = document.getElementById('unitIdSelect');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('bloodType').value = option.dataset.bloodType || '';
        document.getElementById('collectionDate').value = option.dataset.collectionDate || '';
        document.getElementById('expiryDate').value = option.dataset.expiryDate || '';
        
        // Get donor name
        const donorId = option.dataset.donorId || '';
        if (donorId) {
            fetch('../includes/get-donor-name.php?donor_id=' + donorId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('requester').value = data || 'Unknown';
                })
                .catch(() => {
                    document.getElementById('requester').value = 'Unknown';
                });
        } else {
            document.getElementById('requester').value = '';
        }
    } else {
        document.getElementById('requester').value = '';
        document.getElementById('bloodType').value = '';
        document.getElementById('collectionDate').value = '';
        document.getElementById('expiryDate').value = '';
    }
}
</script>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>