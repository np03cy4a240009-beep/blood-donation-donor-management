<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requester = trim($_POST['requester'] ?? '');
    $unit_id = trim($_POST['unit_id'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');
    $unit = (int)($_POST['unit'] ?? 0);
    $collection_date = $_POST['collection_date'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $special_note = trim($_POST['special_note'] ?? '');

    if ($requester === '' || $unit_id === '' || $blood_type === '' || $unit <= 0 || $collection_date === '' || $expiry_date === '') {
        $error = "All fields are required.";
    } elseif ($expiry_date < $collection_date) {
        $error = "Expiry date cannot be earlier than collection date.";
    } else {
        // Check if the unit exists with matching details
        $checkUnit = $conn->prepare("
            SELECT id, status FROM blood_inventory 
            WHERE unit_id = ? AND blood_type = ? AND collection_date = ? AND expiry_date = ?
            LIMIT 1
        ");
        $checkUnit->bind_param("ssss", $unit_id, $blood_type, $collection_date, $expiry_date);
        $checkUnit->execute();
        $unitResult = $checkUnit->get_result();

        if ($unitResult->num_rows === 0) {
            $error = "Unit with specified details not found.";
        } else {
            $row = $unitResult->fetch_assoc();
            $current_status = $row['status'];
            $inventory_id = (int)$row['id'];

            if ($current_status !== 'Available') {
                $error = "This unit is not available for deduction (Current status: " . htmlspecialchars($current_status) . ").";
            } else {
                // Update status to 'reserved' when deducted
                $new_status = 'reserved';

                $stmt = $conn->prepare("
                    UPDATE blood_inventory 
                    SET status = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $new_status, $inventory_id);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Deducted Successfully";
                    header("Location: deduct-inventory.php");
                    exit();
                } else {
                    $error = "Failed to deduct inventory.";
                }
            }
        }
    }
}

$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$bloodGroups = getBloodGroups();
$inventory = $conn->query("
    SELECT unit_id, blood_type, collection_date, expiry_date, status 
    FROM blood_inventory 
    WHERE LOWER(status) = 'available' 
    ORDER BY collection_date DESC
");

$unitsList = [];
if ($inventory) {
    while ($row = $inventory->fetch_assoc()) {
        $unitsList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deduct Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Deduct Inventory</h1>

        <?php if ($error): ?>
            <div class="notice-box notice-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="toast-notification" id="successToast">
                <div class="toast-content">
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                    <button class="toast-close" onclick="closeToast()">&times;</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" class="deduct-inventory-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Requester:</label>
                        <input type="text" name="requester" placeholder="Requester name" required>
                    </div>

                    <div class="form-group">
                        <label>Unit ID:</label>
                        <select name="unit_id" id="unitSelect" required onchange="updateUnitDetails()">
                            <option value="">Select Unit</option>
                            <?php foreach ($unitsList as $unit): ?>
                                <option value="<?php echo htmlspecialchars($unit['unit_id']); ?>" 
                                    data-blood-type="<?php echo htmlspecialchars($unit['blood_type']); ?>"
                                    data-collection-date="<?php echo htmlspecialchars($unit['collection_date']); ?>"
                                    data-expiry-date="<?php echo htmlspecialchars($unit['expiry_date']); ?>">
                                    <?php echo htmlspecialchars($unit['unit_id']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Type:</label>
                        <input type="text" name="blood_type" id="bloodType" placeholder="Blood type" readonly>
                    </div>

                    <div class="form-group">
                        <label>Unit:</label>
                        <input type="number" name="unit" id="unitQuantity" placeholder="Units to deduct" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Collection Date:</label>
                        <input type="date" name="collection_date" id="collectionDate" readonly>
                    </div>

                    <div class="form-group">
                        <label>Expiry Date:</label>
                        <input type="date" name="expiry_date" id="expiryDate" readonly>
                    </div>
                </div>

                <div class="form-group-full">
                    <label>Special note:</label>
                    <textarea name="special_note" placeholder="Optional note for deduction reason"></textarea>
                </div>

                <button class="btn-deduct-inventory-submit" type="submit">Deduct Inventory</button>
            </form>
        </div>
    </div>
</div>

<script>
function updateUnitDetails() {
    const select = document.getElementById('unitSelect');
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById('bloodType').value = selectedOption.getAttribute('data-blood-type');
        document.getElementById('collectionDate').value = selectedOption.getAttribute('data-collection-date');
        document.getElementById('expiryDate').value = selectedOption.getAttribute('data-expiry-date');
        document.getElementById('unitQuantity').value = '1';
    } else {
        document.getElementById('bloodType').value = '';
        document.getElementById('collectionDate').value = '';
        document.getElementById('expiryDate').value = '';
        document.getElementById('unitQuantity').value = '';
    }
}

function closeToast() {
    const toast = document.getElementById('successToast');
    if (toast) {
        toast.classList.add('hide');
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }
}

// Auto-dismiss toast after 4 seconds
window.addEventListener('load', function() {
    const toast = document.getElementById('successToast');
    if (toast) {
        setTimeout(() => {
            closeToast();
        }, 4000);
    }
});
</script>

</body>
</html>
