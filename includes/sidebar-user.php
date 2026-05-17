<div class="sidebar">
    <div class="sidebar-top">
        <a href="profile.php" class="sidebar-brand" aria-label="Open profile">
            <img src="../assets/images/logo.png" alt="">
            <h2>Bloodline Home<br>User</h2>
        </a>

        <?php include __DIR__ . '/theme-toggle.php'; ?>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF'])=='profile.php' ? 'active' : '' ?>">Profile</a>
            <a href="appointments.php" class="<?= basename($_SERVER['PHP_SELF'])=='appointments.php' ? 'active' : '' ?>">Appointment</a>
            <a href="requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='requests.php' ? 'active' : '' ?>">Request</a>
            <a href="emergency.php" class="emergency-link <?= basename($_SERVER['PHP_SELF'])=='emergency.php' ? 'active' : '' ?>">🚨 Emergency</a>
        </div>
    </div>

    <form method="POST" action="../logout.php" style="margin:20px 14px;">
        <?php echo csrfField(); ?>
        <button class="logout-btn" type="submit" style="border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;"><i class="ph-thin ph-sign-out" style="font-size:20px;color:#ffffff;"></i>Logout</button>
    </form>
</div>
