<?php
require_once("includes/security.php");
secureSessionStart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Password - Bloodline Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
    <style>
        .overlay {
            min-height: 100vh;
            background: rgba(0,0,0,0.15);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .modal-box {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 16px;
            padding: 30px;
        }
        .otp-demo {
            background: #fff4d8;
            color: #7a5500;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="overlay">
    <div class="modal-box">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="">
        </div>
        <h1 class="auth-title" style="font-size:28px;">Bloodline Home</h1>
        <h3 style="margin-bottom:14px;">Enter OTP and New Password</h3>

        <?php if (isset($_SESSION['otp_demo'])): ?>
            <div class="otp-demo">Demo OTP: <?php echo $_SESSION['otp_demo']; ?></div>
        <?php endif; ?>

        <form action="auth/reset-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>OTP:</label>
                <input type="text" name="otp" required>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>New password:</label>
                <input type="password" name="new_password" required>
            </div>

            <button class="auth-btn" type="submit">Change password</button>
            <a href="login.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>