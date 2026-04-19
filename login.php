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
            <img src="bloodline.png" alt="logo">
        </div>
        <h1 class="auth-title">Bloodline Home</h1>

        <?php if (isset($_SESSION['password_reset_success'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['password_reset_success']);
                unset($_SESSION['password_reset_success']);
                ?>
            </div>
        <?php endif; ?>

        <form action="auth/login-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" id="emailInput" required>
            </div>

            <div class="form-group" style="margin-top:16px;">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group" style="margin-top:16px; display:none;" id="hospitalField">
                <label>Hospital Name:</label>
                <input type="text" name="hospital_name">
            </div>

            <div class="helper-row">
                <label><input type="checkbox" disabled> Remember me</label>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <button class="auth-btn" type="submit">Log in</button>

            <div class="central-error" id="emailErrorMessage" style="display: none;">
                Invalid email address. Please enter a valid email.
            </div>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="central-error" style="display: block; margin-top: 12px;">
                    <?php 
                    echo htmlspecialchars($_SESSION['login_error']);
                    unset($_SESSION['login_error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="auth-link">
                Don't have an account? 
                <a href="register.php" style="color:red;">Register here</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>