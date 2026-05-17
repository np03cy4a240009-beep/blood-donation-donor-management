<div class="sidebar">
    <div class="sidebar-top">
        <a href="profile.php" class="sidebar-brand" aria-label="Open admin profile">
            <img src="../assets/images/logo.png" alt="">
            <h2><?php
                $brandLabel = $_SESSION['hospital_name'] ?? '';
                if ($brandLabel === '' && !empty($_SESSION['user_full_name'])) {
                    $brandLabel = $_SESSION['user_full_name'];
                }
                echo $brandLabel !== '' ? htmlspecialchars($brandLabel) : 'Bloodline<br>Admin';
            ?></h2>
        </a>

        <?php include __DIR__ . '/theme-toggle.php'; ?>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="profile.php" class="<?= in_array(basename($_SERVER['PHP_SELF']), ['profile.php', 'change-password.php'], true) ? 'active' : '' ?>">Profile</a>
            <a href="donors.php" class="<?= basename($_SERVER['PHP_SELF'])=='donors.php' ? 'active' : '' ?>">Donor</a>
            <a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF'])=='inventory.php' || basename($_SERVER['PHP_SELF'])=='add-inventory.php' || basename($_SERVER['PHP_SELF'])=='inventory-view.php' ? 'active' : '' ?>">Inventory</a>
            <a href="requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='requests.php' ? 'active' : '' ?>">Request</a>
            <a href="emergency-alerts.php" class="emergency-link <?= basename($_SERVER['PHP_SELF'])=='emergency-alerts.php' ? 'active' : '' ?>">🚨 Emergency Alerts</a>
            <a href="testing.php" class="<?= basename($_SERVER['PHP_SELF'])=='testing.php' ? 'active' : '' ?>">Testing</a>
            <a href="appointments.php" class="<?= basename($_SERVER['PHP_SELF'])=='appointments.php' ? 'active' : '' ?>">Appointment</a>
        </div>
    </div>

    <form method="POST" action="../logout.php" style="margin:20px 14px;">
        <?php echo csrfField(); ?>
        <button class="logout-btn" type="submit" style="width:100%;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;"><i class="ph-thin ph-sign-out" style="font-size:20px;color:#ffffff;"></i>Logout</button>
    </form>
</div>