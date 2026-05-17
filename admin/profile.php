<?php
include("../config/admin-session.php");
include("../config/db.php");
include("../includes/functions.php");

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, role, full_name, email, hospital_name, phone, address, city, province, profile_image, created_at, updated_at
    FROM users WHERE id = ? AND role = 'admin' LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    die("Admin account not found.");
}

$totalDonors = 0;
$pendingRequests = 0;
$availableUnits = 0;

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'user'");
$countStmt->execute();
$totalDonors = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM blood_requests WHERE LOWER(status) = 'pending'");
$countStmt->execute();
$pendingRequests = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$countStmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) AS total FROM blood_inventory WHERE LOWER(status) = 'available'");
$countStmt->execute();
$availableUnits = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$memberSince = !empty($admin['created_at']) ? date('M j, Y', strtotime($admin['created_at'])) : 'N/A';
$lastUpdated = !empty($admin['updated_at']) ? date('M j, Y g:i A', strtotime($admin['updated_at'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include("../includes/theme-head.php"); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Bloodline Home</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .admin-profile-avatar {
            width: 88px;
            height: 88px;
            margin: 8px auto 20px;
            border-radius: 50%;
            background: #fff;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--border-soft);
            box-shadow: 0 2px 8px var(--shadow-card);
            flex-shrink: 0;
            aspect-ratio: 1 / 1;
            box-sizing: border-box;
        }
        .admin-profile-avatar img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            display: block;
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-admin.php"); ?>

    <div class="main-panel">
        <div class="topbar" style="justify-content: space-between; width: 100%;">
            <div class="menu-btn">≡</div>
            <a href="dashboard.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;padding:10px 18px;font-size:14px;">
                <i class="ph-bold ph-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <h1 class="page-title">Admin Profile</h1>

        <?php if (isset($_GET['updated'])): ?>
            <div class="notice-box notice-success">Profile updated successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['password_changed'])): ?>
            <div class="notice-box notice-success">Password changed successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'name'): ?>
            <div class="notice-box notice-error">Full name is required.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'phone'): ?>
            <div class="notice-box notice-error">Phone must be a 10-digit number.</div>
        <?php endif; ?>

        <div class="profile-grid">
            <div class="profile-left card">
                <div class="admin-profile-avatar" aria-hidden="true">
                    <img src="../assets/images/logo.png" alt="Bloodline Home">
                </div>

                <h2 style="margin:0 0 6px 0;font-size:22px;margin-top:4px;"><?php echo htmlspecialchars($admin['full_name']); ?></h2>
                <p style="margin:0 0 16px 0;font-size:14px;color:var(--text-secondary);"><?php echo htmlspecialchars($admin['email']); ?></p>

                <span style="display:inline-block;background:#940404;color:#fff;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700;margin-bottom:20px;">Administrator</span>

                <?php if (!empty($admin['hospital_name'])): ?>
                    <p style="margin:0 0 20px 0;font-size:14px;line-height:1.5;">
                        <strong>Organization</strong><br>
                        <?php echo htmlspecialchars($admin['hospital_name']); ?>
                    </p>
                <?php endif; ?>

                <button type="button" onclick="window.location.href='change-password.php'" class="btn" style="width:100%;background:#dc3545;color:#fff;padding:12px 20px;border:none;border-radius:8px;font-weight:700;cursor:pointer;margin-bottom:24px;">
                    <i class="ph-thin ph-lock-key" style="margin-right:6px;"></i> Change Password
                </button>

                <div class="themed-stat-box" style="text-align:left;margin-bottom:14px;">
                    <p style="margin:0 0 6px 0;font-size:13px;font-weight:700;">Registered donors</p>
                    <p style="margin:0;font-size:28px;font-weight:700;"><?php echo $totalDonors; ?></p>
                </div>
                <div class="themed-stat-box" style="text-align:left;margin-bottom:14px;">
                    <p style="margin:0 0 6px 0;font-size:13px;font-weight:700;">Pending requests</p>
                    <p style="margin:0;font-size:28px;font-weight:700;"><?php echo $pendingRequests; ?></p>
                </div>
                <div class="themed-stat-box" style="text-align:left;">
                    <p style="margin:0 0 6px 0;font-size:13px;font-weight:700;">Available blood units</p>
                    <p style="margin:0;font-size:28px;font-weight:700;"><?php echo $availableUnits; ?></p>
                </div>

                <p style="margin:20px 0 0 0;font-size:12px;color:var(--text-muted);line-height:1.6;">
                    Member since: <strong><?php echo htmlspecialchars($memberSince); ?></strong><br>
                    Last updated: <strong><?php echo htmlspecialchars($lastUpdated); ?></strong>
                </p>
            </div>

            <div class="profile-right">
                <form action="update-admin-profile.php" method="POST" class="box card">
                    <?php echo csrfField(); ?>

                    <h3 style="margin:0 0 20px 0;font-size:20px;">Account Details</h3>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                        <div>
                            <label class="themed-form-label">Full Name</label>
                            <input type="text" name="full_name" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>
                        <div>
                            <label class="themed-form-label">Email</label>
                            <input type="email" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;opacity:0.85;" value="<?php echo htmlspecialchars($admin['email']); ?>" disabled>
                            <input type="hidden" name="email_display" value="<?php echo htmlspecialchars($admin['email']); ?>">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                        <div>
                            <label class="themed-form-label">Hospital / Organization</label>
                            <input type="text" name="hospital_name" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['hospital_name'] ?? ''); ?>" placeholder="e.g. Bir Hospital Blood Bank">
                        </div>
                        <div>
                            <label class="themed-form-label">Phone</label>
                            <input type="text" name="phone" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>" placeholder="10-digit phone">
                        </div>
                    </div>

                    <div style="margin-bottom:20px;">
                        <label class="themed-form-label">Address</label>
                        <input type="text" name="address" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['address'] ?? ''); ?>">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                        <div>
                            <label class="themed-form-label">City</label>
                            <input type="text" name="city" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['city'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="themed-form-label">Province</label>
                            <input type="text" name="province" class="themed-field-box" style="width:100%;padding:10px 12px;box-sizing:border-box;" value="<?php echo htmlspecialchars($admin['province'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding:12px 28px;font-weight:700;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/dashboard-scripts.php"); ?>
</body>
</html>
