<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$message = '';

// ─── Handle emergency fulfillment ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fulfill_emergency'])) {
    verifyCsrf();
    $request_id  = trim($_POST['request_id'] ?? '');
    $blood_type  = trim($_POST['blood_type'] ?? '');
    $units_given = (int)($_POST['units_given'] ?? 0);

    if ($request_id && $blood_type && $units_given > 0) {
        $reqStmt = $conn->prepare("SELECT id, units FROM blood_requests WHERE request_id = ? LIMIT 1");
        $reqStmt->bind_param("s", $request_id);
        $reqStmt->execute();
        $reqResult = $reqStmt->get_result();

        if ($reqResult->num_rows > 0) {
            $checkStmt = $conn->prepare("
                SELECT COALESCE(SUM(quantity), 0) AS available FROM blood_inventory
                WHERE blood_type = ? AND LOWER(status) = 'available' AND DATE(expiry_date) >= CURDATE()
            ");
            $checkStmt->bind_param("s", $blood_type);
            $checkStmt->execute();
            $avail = $checkStmt->get_result()->fetch_assoc()['available'] ?? 0;

            if ($avail >= $units_given) {
                $updStmt = $conn->prepare("
                    UPDATE blood_inventory SET status = 'reserved'
                    WHERE blood_type = ? AND LOWER(status) = 'available' AND DATE(expiry_date) >= CURDATE()
                    LIMIT ?
                ");
                $updStmt->bind_param("si", $blood_type, $units_given);
                if ($updStmt->execute()) {
                    $aprStmt = $conn->prepare("UPDATE blood_requests SET status = 'approved' WHERE request_id = ?");
                    $aprStmt->bind_param("s", $request_id);
                    $aprStmt->execute();
                    $message = "success|Emergency fulfilled — {$units_given} unit(s) of " . htmlspecialchars($blood_type) . " reserved.";
                } else {
                    $message = "error|Failed to update inventory.";
                }
            } else {
                $message = "error|Insufficient stock for " . htmlspecialchars($blood_type) . " (available: {$avail}).";
            }
        } else {
            $message = "error|Request not found.";
        }
    } else {
        $message = "error|Fill all fields correctly.";
    }
}

// ─── Handle dismiss notification ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dismiss_alert'])) {
    verifyCsrf();
    $alert_key = trim($_POST['alert_key'] ?? '');
    if ($alert_key !== '') {
        if (!isset($_SESSION['dismissed_alerts'])) $_SESSION['dismissed_alerts'] = [];
        $_SESSION['dismissed_alerts'][$alert_key] = time();
    }
    header("Location: emergency-alerts.php");
    exit();
}

$dismissed = $_SESSION['dismissed_alerts'] ?? [];

// ─── Active emergency requests ────────────────────────────────────────────────
$emergencyStmt = $conn->prepare("
    SELECT br.*, u.full_name, u.city, u.address
    FROM blood_requests br
    LEFT JOIN users u ON br.user_id = u.id
    WHERE br.urgency = 'Urgent' AND LOWER(br.status) = 'pending'
    ORDER BY br.id DESC
");
$emergencyStmt->execute();
$emergencies     = $emergencyStmt->get_result();
$emergencyCount  = $emergencies->num_rows;

// ─── Low stock alerts (< 5 available units per blood type) ───────────────────
$LOW_THRESHOLD = 5;
$lowStockAlerts = [];
foreach (getBloodGroups() as $type) {
    $q = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) total FROM blood_inventory WHERE blood_type = ? AND LOWER(status) = 'available' AND DATE(expiry_date) >= CURDATE()");
    $q->bind_param("s", $type);
    $q->execute();
    $count = (int)$q->get_result()->fetch_assoc()['total'];
    if ($count < $LOW_THRESHOLD) {
        $alertKey = "low_{$type}";
        if (!isset($dismissed[$alertKey]) || (time() - $dismissed[$alertKey]) > 86400) {
            $lowStockAlerts[] = ['type' => $type, 'count' => $count, 'key' => $alertKey];
        }
    }
}

// ─── Expiry alerts: units expiring within 14 days ────────────────────────────
$expiryAlerts = [];
$expiryStmt = $conn->prepare("
    SELECT blood_type, unit_id, expiry_date,
           DATEDIFF(expiry_date, CURDATE()) AS days_left
    FROM blood_inventory
    WHERE LOWER(status) = 'available'
      AND DATE(expiry_date) >= CURDATE()
      AND DATEDIFF(expiry_date, CURDATE()) <= 14
    ORDER BY expiry_date ASC
");
$expiryStmt->execute();
$expiryResult = $expiryStmt->get_result();
while ($row = $expiryResult->fetch_assoc()) {
    $alertKey = "exp_{$row['unit_id']}";
    if (!isset($dismissed[$alertKey])) {
        $expiryAlerts[] = $row + ['key' => $alertKey];
    }
}

// ─── Rare blood group new registrations (past 7 days, not yet acknowledged) ──
$rareGroups = ['A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null'];
$placeholders = implode(',', array_fill(0, count($rareGroups), '?'));
$rareStmt = $conn->prepare("
    SELECT id, full_name, blood_group, created_at
    FROM users
    WHERE role = 'user'
      AND blood_group IN ({$placeholders})
      AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY created_at DESC
");
$rareStmt->bind_param(str_repeat('s', count($rareGroups)), ...$rareGroups);
$rareStmt->execute();
$rareResult = $rareStmt->get_result();
$rareRegistrations = [];
while ($row = $rareResult->fetch_assoc()) {
    $alertKey = "rare_{$row['id']}";
    if (!isset($dismissed[$alertKey])) {
        $rareRegistrations[] = $row + ['key' => $alertKey];
    }
}

// ─── Inventory snapshot (available, non-expired) ──────────────────────────────
$invSnap = [];
$invStmt = $conn->prepare("
    SELECT blood_type, COALESCE(SUM(quantity), 0) total
    FROM blood_inventory
    WHERE LOWER(status) = 'available' AND DATE(expiry_date) >= CURDATE()
    GROUP BY blood_type
");
$invStmt->execute();
$invResult = $invStmt->get_result();
while ($r = $invResult->fetch_assoc()) $invSnap[$r['blood_type']] = (int)$r['total'];

// ─── Parse message ────────────────────────────────────────────────────────────
$msgType = '';
$msgText = '';
if ($message) {
    [$msgType, $msgText] = explode('|', $message, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include("../includes/theme-head.php"); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency &amp; Alerts — Bloodline</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* ── Alert banner ── */
        .alert-banner {
            display:flex; align-items:center; justify-content:space-between;
            padding:14px 20px; border-radius:8px; margin-bottom:16px;
            font-size:14px; font-weight:600;
        }
        .alert-banner.danger  { background:#FCEBEB; color:#791F1F; border:1px solid #F09595; }
        .alert-banner.warning { background:#FAEEDA; color:#633806; border:1px solid #FAC775; }
        .alert-banner.info    { background:#E6F1FB; color:#0C447C; border:1px solid #85B7EB; }
        .alert-banner.success { background:#EAF3DE; color:#27500A; border:1px solid #97C459; }

        /* ── Dismiss button ── */
        .btn-dismiss {
            background:none; border:none; cursor:pointer; font-size:18px;
            opacity:.6; line-height:1; padding:0 4px;
        }
        .btn-dismiss:hover { opacity:1; }

        /* ── Section heading ── */
        .section-heading {
            display:flex; align-items:center; gap:10px;
            font-size:18px; font-weight:700; margin:28px 0 14px;
            color:#940404;
        }

        /* ── Emergency card ── */
        .emergency-card {
            background:white; border:1px solid #F09595;
            border-left:5px solid #dc3545; border-radius:8px;
            padding:22px; margin-bottom:18px;
            box-shadow:0 2px 8px rgba(220,53,69,.08);
        }
        .emergency-card-head {
            display:flex; justify-content:space-between; align-items:center;
            padding-bottom:14px; margin-bottom:14px;
            border-bottom:1px solid #f0f0f0;
        }
        .req-id   { font-size:17px; font-weight:700; color:#dc3545; }
        .urgency-pill {
            background:#dc3545; color:white; padding:5px 14px;
            border-radius:20px; font-size:12px; font-weight:700;
            letter-spacing:.5px;
        }
        .info-grid {
            display:grid; grid-template-columns:1fr 1fr; gap:12px 24px;
            background:#f8f9fa; border-radius:6px; padding:14px;
            margin-bottom:14px; font-size:14px;
        }
        .info-label { color:#666; font-size:11px; font-weight:700; text-transform:uppercase; margin-bottom:2px; }
        .blood-pill {
            display:inline-block; background:#FCEBEB; color:#791F1F;
            border:1px solid #F09595; padding:6px 16px;
            border-radius:20px; font-size:22px; font-weight:700;
        }
        .inv-tags { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
        .inv-tag {
            padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;
            border:1px solid;
        }
        .inv-tag.ok  { background:#EAF3DE; color:#27500A; border-color:#97C459; }
        .inv-tag.bad { background:#FCEBEB; color:#791F1F; border-color:#F09595; }

        /* ── Fulfill form ── */
        .fulfill-form {
            display:flex; gap:12px; align-items:flex-end;
            flex-wrap:wrap; margin-top:16px;
            padding-top:16px; border-top:1px solid #f0f0f0;
        }
        .fulfill-form label { font-size:12px; font-weight:700; color:#555; display:block; margin-bottom:4px; }
        .fulfill-form select,
        .fulfill-form input[type=number] {
            height:38px; padding:6px 10px; border:1px solid #ddd;
            border-radius:5px; font-size:14px;
        }
        .btn-fulfill {
            background:#28a745; color:white; border:none; border-radius:5px;
            padding:9px 20px; font-weight:700; cursor:pointer; font-size:14px;
            height:38px; white-space:nowrap;
        }
        .btn-fulfill:hover { background:#218838; }

        /* ── Rare donor card ── */
        .rare-card {
            display:flex; align-items:center; justify-content:space-between;
            background:#FAEEDA; border:1px solid #FAC775; border-radius:8px;
            padding:14px 18px; margin-bottom:10px;
        }
        .rare-badge {
            background:#633806; color:#FAEEDA; font-size:11px;
            font-weight:700; padding:3px 10px; border-radius:20px;
        }

        /* ── Low stock table ── */
        .stock-row-low  { color:#791F1F; font-weight:700; }
        .stock-row-zero { color:#dc3545; font-weight:700; }

        /* ── Popup notification ── */
        #rare-popup {
            display:none; position:fixed; top:24px; right:24px; z-index:9999;
            background:white; border:2px solid #FAC775; border-radius:10px;
            box-shadow:0 8px 24px rgba(0,0,0,.15); padding:20px 24px;
            max-width:340px; animation:slideIn .3s ease;
        }
        @keyframes slideIn {
            from { transform:translateX(120%); opacity:0; }
            to   { transform:translateX(0);   opacity:1; }
        }
        #rare-popup h3 { margin:0 0 8px; font-size:15px; color:#633806; }
        #rare-popup p  { margin:0; font-size:13px; color:#444; }
        #rare-popup .close-popup {
            position:absolute; top:8px; right:12px; background:none;
            border:none; font-size:18px; cursor:pointer; color:#888;
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Emergency &amp; Alerts</h1>

        <?php if ($msgText): ?>
            <div class="alert-banner <?php echo $msgType === 'success' ? 'success' : 'danger'; ?>">
                <span><?php echo htmlspecialchars($msgText); ?></span>
            </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════════════
             SECTION 1 — ACTIVE EMERGENCY REQUESTS
        ════════════════════════════════════════════════════════════════════ -->
        <div class="section-heading">
            <i class="ph-thin ph-warning" style="font-size:26px;"></i>
            Active Emergencies
            <span style="background:#dc3545;color:white;font-size:12px;padding:2px 10px;border-radius:20px;font-weight:700;">
                <?php echo (int)$emergencyCount; ?>
            </span>
        </div>

        <?php if ($emergencyCount > 0): ?>
            <?php while ($em = $emergencies->fetch_assoc()): ?>
                <div class="emergency-card">
                    <div class="emergency-card-head">
                        <div>
                            <div class="req-id"><?php echo htmlspecialchars($em['request_id']); ?></div>
                            <small class="themed-meta-label">Requested: <?php echo htmlspecialchars($em['request_date'] ?? ($em['created_at'] ?? 'N/A')); ?></small>
                        </div>
                        <span class="urgency-pill">URGENT</span>
                    </div>

                    <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;">
                        <div class="blood-pill"><?php echo htmlspecialchars($em['blood_type']); ?></div>
                        <div>
                            <div style="font-size:22px;font-weight:700;"><?php echo (int)$em['units']; ?> unit(s) needed</div>
                            <?php
                            $avail = $invSnap[$em['blood_type']] ?? 0;
                            $enough = $avail >= $em['units'];
                            ?>
                            <div class="inv-tags">
                                <span class="inv-tag <?php echo $avail > 0 ? 'ok' : 'bad'; ?>">
                                    <?php echo $avail > 0 ? "In stock: {$avail}" : "OUT OF STOCK"; ?>
                                </span>
                                <?php if (!$enough && $avail > 0): ?>
                                    <span class="inv-tag bad">Short by <?php echo (int)($em['units'] - $avail); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="info-grid">
                        <div><div class="info-label">Name</div><?php echo htmlspecialchars($em['full_name'] ?? 'N/A'); ?></div>
                        <div><div class="info-label">Contact</div><?php echo htmlspecialchars($em['contact'] ?? 'N/A'); ?></div>
                        <div><div class="info-label">Location</div><?php echo htmlspecialchars($em['location'] ?? ($em['address'] ?? 'N/A')); ?></div>
                        <div><div class="info-label">City</div><?php echo htmlspecialchars($em['city'] ?? 'N/A'); ?></div>
                    </div>

                    <?php if (!empty($em['notes'])): ?>
                    <div class="emergency-reason-box emergency-reason--warn">
                        <div class="emergency-reason-label">⚠️ Emergency Reason:</div>
                        <div class="emergency-reason-text"><?php echo nl2br(htmlspecialchars($em['notes'])); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($em['status'] === 'pending'): ?>
                    <form method="POST" onsubmit="return confirm('Allocate blood and mark request approved?');">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($em['request_id']); ?>">
                        <div class="fulfill-form">
                            <div>
                                <label>Blood type to allocate</label>
                                <select name="blood_type" required>
                                    <option value="<?php echo htmlspecialchars($em['blood_type']); ?>">
                                        <?php echo htmlspecialchars($em['blood_type']); ?> (primary)
                                    </option>
                                    <?php foreach (getBloodGroups() as $bt):
                                        if ($bt !== $em['blood_type'] && isset($invSnap[$bt]) && $invSnap[$bt] > 0): ?>
                                        <option value="<?php echo htmlspecialchars($bt); ?>">
                                            <?php echo htmlspecialchars($bt); ?> (compatible — <?php echo $invSnap[$bt]; ?> available)
                                        </option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Units to give</label>
                                <input type="number" name="units_given" min="1"
                                       max="<?php echo (int)$em['units']; ?>"
                                       value="<?php echo (int)$em['units']; ?>" required style="width:80px;">
                            </div>
                            <button type="submit" name="fulfill_emergency" class="btn-fulfill">Fulfill emergency</button>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert-banner success" style="margin-top:10px;">Request already <?php echo htmlspecialchars($em['status']); ?>.</div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert-banner success">No active emergency requests — all clear.</div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════════════
             SECTION 2 — LOW STOCK ALERTS
        ════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($lowStockAlerts)): ?>
        <div class="section-heading">
            <i class="ph-thin ph-drop" style="font-size:26px;"></i>
            Low Stock Alerts
            <span style="background:#EF9F27;color:white;font-size:12px;padding:2px 10px;border-radius:20px;font-weight:700;">
                <?php echo count($lowStockAlerts); ?>
            </span>
        </div>

        <?php foreach ($lowStockAlerts as $ls): ?>
            <div class="alert-banner warning" style="justify-content:space-between;">
                <span>
                    <strong><?php echo htmlspecialchars($ls['type']); ?></strong>
                    — only <?php echo (int)$ls['count']; ?> unit(s) available
                    (threshold: <?php echo $LOW_THRESHOLD; ?>)
                </span>
                <form method="POST" style="margin:0;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="alert_key" value="<?php echo htmlspecialchars($ls['key']); ?>">
                    <button name="dismiss_alert" class="btn-dismiss" title="Dismiss for 24h">✕</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════════════
             SECTION 3 — EXPIRY ALERTS (within 14 days / weekly digest)
        ════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($expiryAlerts)): ?>
        <div class="section-heading">
            <i class="ph-thin ph-calendar-x" style="font-size:26px;"></i>
            Expiring Soon
            <span style="background:#EF9F27;color:white;font-size:12px;padding:2px 10px;border-radius:20px;font-weight:700;">
                <?php echo count($expiryAlerts); ?>
            </span>
        </div>

        <div class="table-box">
            <table>
                <thead>
                    <tr>
                        <th>Unique ID</th>
                        <th>Blood type</th>
                        <th>Expiry date</th>
                        <th>Days left</th>
                        <th>Dismiss</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiryAlerts as $ea): ?>
                    <tr class="<?php echo (int)$ea['days_left'] <= 3 ? 'stock-row-zero' : 'stock-row-low'; ?>">
                        <td><?php echo htmlspecialchars($ea['unit_id']); ?></td>
                        <td><?php echo htmlspecialchars($ea['blood_type']); ?></td>
                        <td><?php echo htmlspecialchars($ea['expiry_date']); ?></td>
                        <td><?php echo (int)$ea['days_left']; ?> day(s)</td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="alert_key" value="<?php echo htmlspecialchars($ea['key']); ?>">
                                <button name="dismiss_alert" class="btn-dismiss" title="Dismiss">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════════════════════════════════
             SECTION 4 — RARE BLOOD GROUP REGISTRATIONS
        ════════════════════════════════════════════════════════════════════ -->
        <?php if (!empty($rareRegistrations)): ?>
        <div class="section-heading">
            <i class="ph-thin ph-star" style="font-size:26px;"></i>
            Rare Blood Group Registrations
            <span style="background:#BA7517;color:white;font-size:12px;padding:2px 10px;border-radius:20px;font-weight:700;">
                <?php echo count($rareRegistrations); ?>
            </span>
        </div>

        <?php foreach ($rareRegistrations as $rr): ?>
            <div class="rare-card">
                <div>
                    <span class="rare-badge"><?php echo htmlspecialchars($rr['blood_group']); ?></span>
                    &nbsp;
                    <strong>DID <?php echo (int)$rr['id']; ?></strong>
                    — <?php echo htmlspecialchars($rr['full_name']); ?>
                    <div style="font-size:12px;color:#855;margin-top:2px;">
                        Registered: <?php echo htmlspecialchars($rr['created_at']); ?>
                    </div>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="donor-profile.php?id=<?php echo (int)$rr['id']; ?>" class="btn btn-light" style="font-size:13px;">View profile</a>
                    <form method="POST" style="margin:0;">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="alert_key" value="<?php echo htmlspecialchars($rr['key']); ?>">
                        <button name="dismiss_alert" class="btn-dismiss" title="Dismiss">✕</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($lowStockAlerts) && empty($expiryAlerts) && empty($rareRegistrations) && $emergencyCount === 0): ?>
            <div class="alert-banner success" style="margin-top:16px;">All systems normal — no alerts at this time.</div>
        <?php endif; ?>

    </div><!-- /main-panel -->
</div><!-- /dashboard-layout -->

<!-- ═══════════════════════════════════════════════════════════════════════
     RARE BLOOD REGISTRATION POPUP
     Shown once per page load when there are new rare registrations
════════════════════════════════════════════════════════════════════════ -->
<?php if (!empty($rareRegistrations)): ?>
<div id="rare-popup">
    <button class="close-popup" onclick="document.getElementById('rare-popup').style.display='none'">✕</button>
    <h3>⚠ Rare Blood Group Registered</h3>
    <p>
        <?php echo count($rareRegistrations); ?> rare donor(s) registered in the last 7 days.<br>
        Groups: <?php echo htmlspecialchars(implode(', ', array_unique(array_column($rareRegistrations, 'blood_group')))); ?>
    </p>
    <a href="#rare-section" onclick="document.getElementById('rare-popup').style.display='none'"
       style="display:inline-block;margin-top:10px;font-size:13px;color:#940404;font-weight:700;">
        View list →
    </a>
</div>
<script>
    setTimeout(function() {
        document.getElementById('rare-popup').style.display = 'block';
    }, 800);
</script>
<?php endif; ?>

<?php include("../includes/dashboard-scripts.php"); ?>
</body>
</html>
