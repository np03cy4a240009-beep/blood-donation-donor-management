<?php
require_once("includes/security.php");
secureSessionStart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bloodline Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card small">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="logo">
        </div>
        <h1 class="auth-title">Bloodline Home</h1>

        <form action="auth/login-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="helper-row">
                <label><input type="checkbox" disabled> Remember me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <button class="auth-btn" type="submit">Log in</button>

            <div class="auth-link">
                Don't have an account? 
                <a href="register.php" style="color:red;">Register here</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>