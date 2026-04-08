<?php
require_once("includes/security.php");
secureSessionStart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email OTP - Bloodline Home</title>
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
    </style>
</head>
<body>
<div class="overlay">
    <div class="modal-box">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="">
        </div>
        <h1 class="auth-title" style="font-size:28px;">Bloodline Home</h1>
        <h3 style="margin-bottom:14px;">Enter your Email to get OTP</h3>

        <form action="auth/otp-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <button class="auth-btn" type="submit">Get OTP</button>
            <a href="login.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>