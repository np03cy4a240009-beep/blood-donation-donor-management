<?php
/**
 * dashboard-notifications.php
 * ─────────────────────────────────────────────────────────────────────
 * Drop this partial anywhere inside dashboard.php's main-panel to render
 * a live notification banner linking to emergency-alerts.php.
 *
 * Usage in dashboard.php (after the stats row):
 *   <?php include("../includes/dashboard-notifications.php"); ?>
 *
 * Requires: $conn (MySQLi connection already open)
 * ─────────────────────────────────────────────────────────────────────
 */

// ── 1. Active urgent / pending requests ──────────────────────────────
$notif_urgent = 0;
$q = $conn->prepare("SELECT COUNT(*) total FROM blood_requests WHERE urgency='Urgent' AND LOWER(status)='pending'");
$q->execute();
$notif_urgent = (int)$q->get_result()->fetch_assoc()['total'];
$q->close();

// ── 2. Low stock (< 5 available, non-expired) ────────────────────────
$LOW  = 5;
$notif_low = 0;
$q = $conn->prepare("
    SELECT COUNT(DISTINCT blood_type) total FROM blood_inventory
    WHERE LOWER(status)='available' AND DATE(expiry_date) >= CURDATE()
    GROUP BY blood_type HAVING COUNT(*) < ?
");
// Simpler: count types below threshold
$lowTypes = [];
foreach (getBloodGroups() as $bt) {
    $q2 = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) c FROM blood_inventory WHERE blood_type=? AND LOWER(status)='available' AND DATE(expiry_date)>=CURDATE()");
    $q2->bind_param("s", $bt);
    $q2->execute();
    $c = (int)$q2->get_result()->fetch_assoc()['c'];
    if ($c < $LOW) $lowTypes[] = $bt;
    $q2->close();
}
$notif_low = count($lowTypes);

// ── 3. Units expiring within 14 days ────────────────────────────────
$q3 = $conn->prepare("
    SELECT COALESCE(SUM(quantity), 0) total FROM blood_inventory
    WHERE LOWER(status)='available'
      AND DATE(expiry_date) >= CURDATE()
      AND DATEDIFF(expiry_date, CURDATE()) <= 14
");
$q3->execute();
$notif_expiry = (int)$q3->get_result()->fetch_assoc()['total'];
$q3->close();

// ── 4. New rare donors (past 7 days) ────────────────────────────────
$rareGroups = ['A2-', 'A2B-', 'Bombay (Oh)', 'Rh-null'];
$ph = implode(',', array_fill(0, count($rareGroups), '?'));
$q4 = $conn->prepare("SELECT COUNT(*) total FROM users WHERE role='user' AND blood_group IN ({$ph}) AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$q4->bind_param(str_repeat('s', count($rareGroups)), ...$rareGroups);
$q4->execute();
$notif_rare = (int)$q4->get_result()->fetch_assoc()['total'];
$q4->close();

$totalAlerts = $notif_urgent + $notif_low + $notif_expiry + $notif_rare;
if ($totalAlerts === 0) return; // nothing to show
?>

<!-- ─── Dashboard Notification Strip ─────────────────────────────── -->
<div id="dash-notif-strip" style="
    display:flex; flex-wrap:wrap; gap:10px; margin:0 0 24px;
    padding:14px 18px; border-radius:8px;
    background:#FAEEDA; border:1px solid #FAC775;
    align-items:center; justify-content:space-between;
">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        <span style="font-weight:700; font-size:14px; color:#633806;">
            ⚡ <?php echo $totalAlerts; ?> alert<?php echo $totalAlerts !== 1 ? 's' : ''; ?> need attention
        </span>

        <?php if ($notif_urgent > 0): ?>
            <span style="background:#FCEBEB; color:#791F1F; border:1px solid #F09595; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                <?php echo $notif_urgent; ?> emergency
            </span>
        <?php endif; ?>

        <?php if ($notif_low > 0): ?>
            <span style="background:#FAEEDA; color:#633806; border:1px solid #FAC775; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                <?php echo $notif_low; ?> low stock
            </span>
        <?php endif; ?>

        <?php if ($notif_expiry > 0): ?>
            <span style="background:#FAEEDA; color:#633806; border:1px solid #FAC775; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                <?php echo $notif_expiry; ?> expiring soon
            </span>
        <?php endif; ?>

        <?php if ($notif_rare > 0): ?>
            <span style="background:#FAEEDA; color:#633806; border:1px solid #FAC775; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700;">
                <?php echo $notif_rare; ?> rare donor<?php echo $notif_rare !== 1 ? 's' : ''; ?>
            </span>
        <?php endif; ?>
    </div>

    <a href="../admin/emergency-alerts.php"
       style="background:#940404; color:white; padding:7px 18px;
              border-radius:6px; font-size:13px; font-weight:700;
              text-decoration:none; white-space:nowrap;">
        View all alerts →
    </a>
</div>

<?php
// ── Auto-popup if urgent emergencies exist (shown once per session) ──────────
if ($notif_urgent > 0 && empty($_SESSION['popup_shown_' . date('Y-m-d')])): 
    $_SESSION['popup_shown_' . date('Y-m-d')] = true;
?>
<div id="dash-urgency-popup" style="
    display:none; position:fixed; top:24px; right:24px; z-index:9999;
    background:white; border:2px solid #F09595; border-radius:10px;
    box-shadow:0 8px 24px rgba(0,0,0,.18); padding:20px 24px; max-width:320px;
">
    <button onclick="document.getElementById('dash-urgency-popup').style.display='none'"
            style="position:absolute;top:8px;right:12px;background:none;border:none;font-size:18px;cursor:pointer;color:#888;">✕</button>
    <h3 style="margin:0 0 8px;font-size:15px;color:#791F1F;">Urgent blood request<?php echo $notif_urgent !== 1 ? 's' : ''; ?></h3>
    <p style="margin:0;font-size:13px;color:#444;">
        <?php echo $notif_urgent; ?> pending urgent request<?php echo $notif_urgent !== 1 ? 's' : ''; ?> require your action.
    </p>
    <a href="../admin/emergency-alerts.php"
       style="display:inline-block;margin-top:12px;background:#dc3545;color:white;
              padding:7px 16px;border-radius:6px;font-size:13px;font-weight:700;
              text-decoration:none;">
        Respond now →
    </a>
</div>
<script>
setTimeout(function(){ document.getElementById('dash-urgency-popup').style.display='block'; }, 600);
</script>
<?php endif; ?>
