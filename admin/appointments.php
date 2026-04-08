<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT appointments.*, users.full_name, users.blood_group
    FROM appointments
    LEFT JOIN users ON appointments.user_id = users.id
    WHERE 1=1
";
$params = [];
$types = '';

if ($statusFilter !== '') {
    $sql .= " AND appointments.status = ? ";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($dateFilter !== '') {
    $sql .= " AND appointments.appointment_date = ? ";
    $params[] = $dateFilter;
    $types .= 's';
}

if ($search !== '') {
    $sql .= " AND (users.full_name LIKE ? OR appointments.location LIKE ? OR appointments.id LIKE ?) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql .= " ORDER BY appointments.appointment_date DESC, appointments.appointment_time ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

$today = date('Y-m-d');

// Secure query with prepared statements to prevent SQL injection
$todayStmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = ?");
$todayStmt->bind_param("s", $today);
$todayStmt->execute();
$totalToday = $todayStmt->get_result()->fetch_assoc()['total'] ?? 0;

$confirmedStatus = 'confirmed';
$confirmedStmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status = ?");
$confirmedStmt->bind_param("s", $confirmedStatus);
$confirmedStmt->execute();
$confirmed = $confirmedStmt->get_result()->fetch_assoc()['total'] ?? 0;

$pendingStatus = 'pending';
$pendingStmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status = ?");
$pendingStmt->bind_param("s", $pendingStatus);
$pendingStmt->execute();
$pending = $pendingStmt->get_result()->fetch_assoc()['total'] ?? 0;

$cancelledStatus = 'cancelled';
$cancelledStmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status = ?");
$cancelledStmt->bind_param("s", $cancelledStatus);
$cancelledStmt->execute();
$cancelled = $cancelledStmt->get_result()->fetch_assoc()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Appointment</h1>

        <?php if (isset($_GET['updated'])): ?>
            <div class="notice-box notice-success" style="max-width:1000px;margin:0 auto 20px;">✓ Appointment status updated successfully. Donation recorded.</div>
        <?php endif; ?>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach (getBloodGroups() as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>">
                            <?php echo htmlspecialchars($group); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if($statusFilter==='confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="cancelled" <?php if($statusFilter==='cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
                <button type="submit">Apply</button>
                <a href="appointments.php" class="btn-clear">Clear</a>
            </form>
        </div>

        <h2>Total Numbers</h2>
        <div class="stats-row">
            <div class="stat-card"><div><h4>Today</h4><p><?php echo (int)$totalToday; ?></p></div><span>📅</span></div>
            <div class="stat-card"><div><h4>Confirmed</h4><p><?php echo (int)$confirmed; ?></p></div><span>✓</span></div>
            <div class="stat-card"><div><h4>Pending</h4><p><?php echo (int)$pending; ?></p></div><span>⏳</span></div>
            <div class="stat-card"><div><h4>Cancelled</h4><p><?php echo (int)$cancelled; ?></p></div><span>⊘</span></div>
        </div>

        <h2>Appointment List</h2>
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>location</th>
                        <th>Blood Type</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($appointments->num_rows > 0): ?>
                        <?php while($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td>AID <?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td>
                                <span class="<?php echo getBloodGroupBadgeClass($row['blood_group']); ?>">
                                    <?php echo htmlspecialchars($row['blood_group']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td class="table-actions">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <form method="POST" action="update-appointment-status.php" style="display:inline;">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button class="btn btn-success" type="submit">Confirm</button>
                                        </form>

                                        <form method="POST" action="update-appointment-status.php" style="display:inline;">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="btn btn-danger" type="submit">Cancel</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No appointments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>