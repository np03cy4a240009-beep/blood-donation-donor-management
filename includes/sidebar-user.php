<div class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <img src="../assets/images/logo.png" alt="">
            <h2>Bloodline<br>User</h2>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="profile.php" class="<?= basename($_SERVER['PHP_SELF'])=='profile.php' ? 'active' : '' ?>">Profile</a>
            <a href="requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='requests.php' ? 'active' : '' ?>">Request</a>
            <a href="appointments.php" class="<?= basename($_SERVER['PHP_SELF'])=='appointments.php' ? 'active' : '' ?>">Appointment</a>
            <a href="change-password.php" class="<?= basename($_SERVER['PHP_SELF'])=='change-password.php' ? 'active' : '' ?>">Password</a>
        </div>
    </div>

    <form method="POST" action="../logout.php" style="margin:20px 14px;">
        <?php echo csrfField(); ?>
        <button class="logout-btn" type="submit" style="width:100%;border:none;cursor:pointer;">Logout</button>
    </form>
</div>