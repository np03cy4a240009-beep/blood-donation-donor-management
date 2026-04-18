<!-- Admin navigation sidebar used across dashboard pages. -->
<div class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <img src="../assets/images/logo.png" alt="">
            <h2>Bloodline<br>Admin</h2>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="donors.php" class="<?= basename($_SERVER['PHP_SELF'])=='donors.php' ? 'active' : '' ?>">Donor</a>
            <a href="rare-blood-group.php" class="<?= basename($_SERVER['PHP_SELF'])=='rare-blood-group.php' ? 'active' : '' ?>">Rare Blood Group</a>
            <a href="inventory.php" class="<?= basename($_SERVER['PHP_SELF'])=='inventory.php' || basename($_SERVER['PHP_SELF'])=='add-inventory.php' || basename($_SERVER['PHP_SELF'])=='deduct-inventory.php' || basename($_SERVER['PHP_SELF'])=='inventory-view.php' ? 'active' : '' ?>">Inventory</a>
            <a href="requests.php" class="<?= basename($_SERVER['PHP_SELF'])=='requests.php' ? 'active' : '' ?>">Request</a>
            <a href="testing.php" class="<?= basename($_SERVER['PHP_SELF'])=='testing.php' ? 'active' : '' ?>">Testing</a>
            <a href="appointments.php" class="<?= basename($_SERVER['PHP_SELF'])=='appointments.php' ? 'active' : '' ?>">Appointment</a>
        </div>
    </div>

    <form method="POST" action="../logout.php" style="margin:20px 14px;">
        <?php echo csrfField(); ?>
        <button class="logout-btn" type="submit" style="width:100%;border:none;cursor:pointer;">Logout</button>
    </form>
</div>