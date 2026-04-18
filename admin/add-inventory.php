<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

// Validate the form and register a new blood unit in inventory.
$error = '';
$success = false;

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
                    $success = true;
                } else {
                    $error = "Failed to add inventory.";
                }
            }
        }
    }
}

$stmt = $conn->prepare("SELECT id, full_name, blood_group FROM users WHERE role='user' ORDER BY full_name ASC");
$stmt->execute();
$donors = $stmt->get_result();
$bloodGroups = getBloodGroups();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Inventory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <h1 class="page-title">Add Inventory</h1>

        <?php if ($error): ?>
            <div class="notice-box notice-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="successMessage" style="position:fixed;bottom:40px;right:40px;background:white;padding:20px 30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:15px;z-index:1000;font-weight:600;">
                <span>Added Successfully</span>
                <button onclick="closeSuccessMessage()" style="background:none;border:none;cursor:pointer;font-size:20px;color:#666;">✕</button>
            </div>
            <script>
                function closeSuccessMessage() {
                    document.getElementById('successMessage').style.display = 'none';
                }
                setTimeout(function() {
                    window.location.href = 'inventory.php?added=1';
                }, 2000);
            </script>
        <?php endif; ?>

        <div class="card" style="padding:40px;max-width:900px;margin:auto;">
            <form method="POST">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-bottom:24px;">
                    <!-- Left Column -->
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:8px;">Donor:</label>
                        <select name="donor_id" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>
                            <option value="">Select Donor</option>
                            <?php if ($donors): ?>
                                <?php while($donor = $donors->fetch_assoc()): ?>
                                    <option value="<?php echo (int)$donor['id']; ?>">
                                        <?php echo htmlspecialchars($donor['full_name'] . " (ID: " . $donor['id'] . " / " . ($donor['blood_group'] ?? 'N/A') . ")"); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Blood Type:</label>
                        <select name="blood_type" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>
                            <option value="">Select Blood Type</option>
                            <?php foreach ($bloodGroups as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Collection Date:</label>
                        <input type="date" name="collection_date" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>
                    </div>

                    <!-- Right Column -->
                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:8px;">Unit ID:</label>
                        <input type="text" name="unit_id" placeholder="" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Status:</label>
                        <select name="status" style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>
                            <option value="Available">Available</option>
                            <option value="expired">expired</option>
                            <option value="unsafe">unsafe</option>
                            <option value="reserved">reserved</option>
                        </select>

                        <label style="display:block;font-weight:600;margin-bottom:8px;">Expiry Date:</label>
                        <input type="date" name="expiry_date" required style="width:100%;height:42px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;" <?php if($success) echo 'disabled'; ?>>
                    </div>
                </div>

                <!-- Full Width Special Note -->
                <label style="display:block;font-weight:600;margin-bottom:8px;">Special note:</label>
                <textarea name="special_note" placeholder="Optional note for rare groups or special handling" style="width:100%;min-height:120px;margin-bottom:24px;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:inherit;" <?php if($success) echo 'disabled'; ?>></textarea>

                <button class="btn" type="submit" style="width:100%;background:#dc3545;color:white;padding:14px;border:none;border-radius:6px;cursor:pointer;font-weight:700;font-size:16px;margin-bottom:12px;" <?php if($success) echo 'disabled'; ?>>Add Inventory</button>
                <a href="inventory.php" class="btn btn-light" style="display:block;text-align:center;padding:14px;border-radius:6px;text-decoration:none;background:#f0f0f0;color:#333;">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>