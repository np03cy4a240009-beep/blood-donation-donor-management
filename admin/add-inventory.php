<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_id = (int)($_POST['donor_id'] ?? 0);
    $unit_id = trim($_POST['unit_id'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');
    $collection_date = $_POST['collection_date'] ?? '';
    $expiry_date = $_POST['expiry_date'] ?? '';
    $status = trim($_POST['status'] ?? 'Available');
    $special_note = trim($_POST['special_note'] ?? '');

    if ($donor_id <= 0 || $unit_id === '' || $blood_type === '' || $collection_date === '' || $expiry_date === '') {
        $error = "All fields are required.";
    } elseif ($expiry_date < $collection_date) {
        $error = "Expiry date cannot be earlier than collection date.";
    } else {
        $checkDonor = $conn->prepare("SELECT id FROM users WHERE id = ? AND role='user' LIMIT 1");
        $checkDonor->bind_param("i", $donor_id);
        $checkDonor->execute();
        $donorResult = $checkDonor->get_result();

        if ($donorResult->num_rows === 0) {
            $error = "Invalid donor ID.";
        } else {
            $checkUnit = $conn->prepare("SELECT id FROM blood_inventory WHERE unit_id = ? LIMIT 1");
            $checkUnit->bind_param("s", $unit_id);
            $checkUnit->execute();
            $unitResult = $checkUnit->get_result();

            if ($unitResult->num_rows > 0) {
                $error = "Unit ID already exists.";
            } else {
                $hasSpecialNoteColumn = false;
                $columnCheck = $conn->query("SHOW COLUMNS FROM blood_inventory LIKE 'special_note'");
                if ($columnCheck && $columnCheck->num_rows > 0) {
                    $hasSpecialNoteColumn = true;
                }

                if ($hasSpecialNoteColumn) {
                    $stmt = $conn->prepare("
                        INSERT INTO blood_inventory (donor_id, unit_id, blood_type, collection_date, expiry_date, status, special_note)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("issssss", $donor_id, $unit_id, $blood_type, $collection_date, $expiry_date, $status, $special_note);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO blood_inventory (donor_id, unit_id, blood_type, collection_date, expiry_date, status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("isssss", $donor_id, $unit_id, $blood_type, $collection_date, $expiry_date, $status);
                }

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Added Successfully";
                    header("Location: add-inventory.php");
                    exit();
                } else {
                    $error = "Failed to add inventory.";
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

$donors = $conn->query("SELECT id, full_name, blood_group FROM users WHERE role='user' ORDER BY full_name ASC");
$bloodGroups = getBloodGroups();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Add Inventory</h1>

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
            <form method="POST" class="add-inventory-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Donor:</label>
                        <select name="donor_id" required>
                            <option value="">Select Donor</option>
                            <?php if ($donors): ?>
                                <?php while($donor = $donors->fetch_assoc()): ?>
                                    <option value="<?php echo (int)$donor['id']; ?>">
                                        <?php echo htmlspecialchars($donor['full_name'] . " (ID: " . $donor['id'] . " / " . ($donor['blood_group'] ?? 'N/A') . ")"); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Unit ID:</label>
                        <input type="text" name="unit_id" placeholder="Unit ID" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Type:</label>
                        <select name="blood_type" required>
                            <option value="">Select Blood Type</option>
                            <?php foreach ($bloodGroups as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="Available">Available</option>
                            <option value="expired">Expired</option>
                            <option value="unsafe">Unsafe</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Collection Date:</label>
                        <input type="date" name="collection_date" required>
                    </div>

                    <div class="form-group">
                        <label>Expiry Date:</label>
                        <input type="date" name="expiry_date" required>
                    </div>
                </div>

                <div class="form-group-full">
                    <label>Special note:</label>
                    <textarea name="special_note" placeholder="Optional note for rare groups or special handling"></textarea>
                </div>

                <button class="btn-add-inventory-submit" type="submit">Add Inventory</button>
            </form>
        </div>
    </div>
</div>

<script>
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