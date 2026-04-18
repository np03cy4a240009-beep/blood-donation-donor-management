<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$statusFilter = trim($_GET['status'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$search = trim($_GET['search'] ?? '');
$bloodFilter = trim($_GET['blood_group'] ?? '');

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

if ($bloodFilter !== '') {
    $sql .= " AND users.blood_group = ? ";
    $params[] = $bloodFilter;
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

// Total Today
$stmt1 = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date=?");
$stmt1->bind_param("s", $today);
$stmt1->execute();
$totalToday = $stmt1->get_result()->fetch_assoc()['total'] ?? 0;
$stmt1->close();

// Confirmed
$stmt2 = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status='confirmed'");
$stmt2->execute();
$confirmed = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;
$stmt2->close();

// Pending
$stmt3 = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status='pending'");
$stmt3->execute();
$pending = $stmt3->get_result()->fetch_assoc()['total'] ?? 0;
$stmt3->close();

// Cancelled
$stmt4 = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE status='cancelled'");
$stmt4->execute();
$cancelled = $stmt4->get_result()->fetch_assoc()['total'] ?? 0;
$stmt4->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/phosphor-icons"></script>
</head>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>
    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Appointment</h1>

        <h2 style="font-weight:normal;">Total Numbers</h2>
        <div class="stats-row" style="margin-bottom:30px;">
            <div class="stat-card"><div><h4>Today</h4><p><?php echo (int)$totalToday; ?></p></div><i class="ph-thin ph-calendar-plus" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Confirmed</h4><p><?php echo (int)$confirmed; ?></p></div><i class="ph-thin ph-check-circle-fill" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Pending</h4><p><?php echo (int)$pending; ?></p></div><i class="ph-thin ph-clipboard" style="color:#940404;font-size:52px;"></i></div>
            <div class="stat-card"><div><h4>Cancelled</h4><p><?php echo (int)$cancelled; ?></p></div><i class="ph-thin ph-prohibit" style="color:#940404;font-size:52px;"></i></div>
        </div>

        <div class="filter-box">
            <h3>Filter</h3>
            <form method="GET" class="filter-row">
                <input type="text" name="search" placeholder="name & ID" value="<?php echo htmlspecialchars($search); ?>">
                <input type="date" name="date" placeholder="Date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                <select name="blood_group">
                    <option value="">Blood group</option>
                    <?php foreach (getBloodGroups() as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>"><?php echo htmlspecialchars($group); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status">
                    <option value="">Status</option>
                    <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if($statusFilter==='confirmed') echo 'selected'; ?>>Confirmed</option>
                    <option value="cancelled" <?php if($statusFilter==='cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
                <button class="btn" type="submit" style="background:#666;color:white;">Clear</button>
            </form>
        </div>

        <h2>Appointment List</h2>
        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Location</th>
                        <th>Blood Type</th>
                        <th>Time</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td>AID <?php echo (int)$row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['blood_group']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>

                                    <form method="POST" action="update-appointment-status.php" style="display:inline;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="status" value="confirmed">
                                        <button class="btn" type="submit" style="background:#d4a574;color:white;padding:6px 16px;border:none;border-radius:4px;cursor:pointer;font-weight:600;">Confirm</button>
                                    </form>

                                    <form method="POST" action="update-appointment-status.php" style="display:inline;margin-left:8px;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button class="btn" type="submit" style="background:#dc3545;color:white;padding:6px 16px;border:none;border-radius:4px;cursor:pointer;font-weight:600;">Cancel</button>
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