<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/security.php"); // For csrfField() and csrfCheck()

$user_id = $_SESSION['user_id'];
$message = '';

$userStmt = $conn->prepare("SELECT blood_group FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();
$userBloodGroup = $userData['blood_group'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_request'])) {
    if (!csrfCheck($_POST['csrf_token'] ?? '')) {
        $message = "Invalid CSRF token.";
    } else {
        $delete_id = (int)$_POST['delete_request'];
        $delStmt = $conn->prepare("DELETE FROM blood_requests WHERE id = ? AND user_id = ?");
        $delStmt->bind_param("ii", $delete_id, $user_id);
        if ($delStmt->execute()) {
            header("Location: requests.php?deleted=1");
            exit();
        } else {
            $message = "Failed to delete request.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hospital_name'])) {
    $request_id = "RID" . time() . rand(10, 99);
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $blood_type = trim($_POST['blood_type'] ?? '');
    $units = (int)($_POST['units'] ?? 0);
    $urgency = trim($_POST['urgency'] ?? 'Normal');
    $request_date = $_POST['request_date'] ?? '';
    $required_by = $_POST['required_by'] ?? '';
    $status = 'pending';

    if ($hospital_name === '' || $contact === '' || $location === '' || $blood_type === '' || $units <= 0 || $request_date === '' || $required_by === '') {
        $message = "Please fill all required fields correctly.";
    } elseif ($required_by < $request_date) {
        $message = "Required by date must be same as or after request date.";
    } else {
        if (isRareBloodGroup($blood_type)) {
            $urgency = 'Urgent';
        }

        $stmt = $conn->prepare("INSERT INTO blood_requests (request_id, user_id, hospital_name, contact, location, blood_type, units, urgency, status, request_date, required_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissssissss", $request_id, $user_id, $hospital_name, $contact, $location, $blood_type, $units, $urgency, $status, $request_date, $required_by);

        if ($stmt->execute()) {
            header("Location: requests.php?sent=1");
            exit();
        } else {
            $message = "Failed to send request.";
        }
    }
}

$history = $conn->prepare("SELECT * FROM blood_requests WHERE user_id = ? ORDER BY id DESC");
$history->bind_param("i", $user_id);
$history->execute();
$requests = $history->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Request</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-request-organized.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Request</h1>

        <?php if (isset($_GET['sent'])): ?>
            <div class="notice-box notice-success">Request sent successfully.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
            <div class="notice-box notice-success">Request deleted successfully.</div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="notice-box notice-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (isRareBloodGroup($userBloodGroup)): ?>
            <div class="notice-box notice-info">
                Rare blood group exception: your requests are treated with priority review and urgent handling when applicable.
            </div>
        <?php endif; ?>

        <h2>Send Request</h2>
        <div class="slot-panel">
            <form method="POST">
                <?php echo csrfField(); ?>
                <div style="display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:20px;align-items:start;">
                    <div>
                        <p><strong>RID:</strong> Auto Generate</p>
                        <br>
                        <p>Name: <input type="text" name="hospital_name" style="width:100%;height:34px;" required></p>
                        <p style="margin-top:10px;">Contact: <input type="text" name="contact" style="width:100%;height:34px;" required></p>
                        <p style="margin-top:10px;">Location: <input type="text" name="location" style="width:100%;height:34px;" required></p>
                    </div>
                    <div>
                        <p><strong>Status:</strong> pending</p>
                        <br>
                        <p>Request Date: <input type="date" name="request_date" style="width:100%;height:34px;" required></p>
                        <p style="margin-top:10px;">Required By: <input type="date" name="required_by" style="width:100%;height:34px;" required></p>
                        <p style="margin-top:10px;">Urgency:
                            <select name="urgency" style="width:100%;height:34px;">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </p>
                    </div>
                    <div class="card" style="padding:16px;color:#222;">
                        <p><strong>Blood Type</strong></p>
                        <select name="blood_type" style="width:100%;height:34px;" required>
                            <option value="">Select</option>
                            <?php foreach (getBloodGroups() as $group): ?>
                                <option value="<?php echo htmlspecialchars($group); ?>"><?php echo htmlspecialchars($group); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p style="margin-top:10px;"><strong>Units</strong></p>
                        <input type="number" name="units" min="1" style="width:100%;height:34px;" required>
                    </div>
                </div>

                <button class="btn" style="margin-top:20px;background:#b13232;color:#fff;width:100%;">Send Request</button>
            </form>
        </div>

        <h2 style="margin-top:30px;">Request History</h2>
        <div class="slot-panel">
            <?php if ($requests->num_rows > 0): ?>
                <?php while($row = $requests->fetch_assoc()): ?>
                <div class="slot-item">
                    <div>
                        <?php echo htmlspecialchars($row['hospital_name']); ?><br>
                        <small>
                            <span class="<?php echo getBloodGroupBadgeClass($row['blood_type']); ?>">
                                <?php echo htmlspecialchars($row['blood_type']); ?>
                            </span>
                            | <?php echo htmlspecialchars($row['units']); ?> Units
                            | <?php echo htmlspecialchars($row['request_id']); ?>
                        </small>
                    </div>
                    <div>
                        <span class="<?php echo getStatusBadge($row['status']); ?>">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                        <form method="POST" action="requests.php" style="display:inline;" onsubmit="return confirm('Delete this request?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="delete_request" value="<?php echo (int)$row['id']; ?>">
                            <button class="btn btn-light" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card" style="padding:16px;color:#222;">No request history found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
</body>
</html>