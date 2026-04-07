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

        <div class="stats-row">
            <div class="stat-card"><div><h4>Today</h4><p><?php echo (int)$totalToday; ?></p></div><span>[A]</span></div>
            <div class="stat-card"><div><h4>Confirmed</h4><p><?php echo (int)$confirmed; ?></p></div><span>[OK]</span></div>
            <div class="stat-card"><div><h4>Pending</h4><p><?php echo (int)$pending; ?></p></div><span>[~]</span></div>
            <div class="stat-card"><div><h4>Cancelled</h4><p><?php echo (int)$cancelled; ?></p></div><span>[X]</span></div>
        </div>

        <div class="filter-box">
            <h3>Filter Appointments</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="Name / location / ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if($statusFilter==='confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="cancelled" <?php if($statusFilter==='cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
                <button class="btn" type="submit">Filter</button>
            </form>
        </div>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Blood Group</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>

                                    <form method="POST" action="update-appointment-status.php" style="display:inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn btn-light" type="submit">Confirm</button>
                                    </form>

                                    <form method="POST" action="update-appointment-status.php" style="display:inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn btn-light" type="submit">Cancel</button>
                                    </form>

                                <?php else: ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>