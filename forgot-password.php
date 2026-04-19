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

        <?php if (isset($_SESSION['otp_error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['otp_error']);
                unset($_SESSION['otp_error']);
                ?>
            </div>
        <?php endif; ?>

        <form action="auth/otp-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" name="email" required>
                <div id="emailError" style="color: #dc3545; font-size: 12px; margin-top: 5px; display: none;"></div>
            </div>

            <button class="auth-btn" type="submit">Get OTP</button>
            <a href="login.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;">Cancel</a>
        </form>
    </div>
</div>

<script>
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

const form = document.querySelector('form');
const emailInput = document.getElementById('email');
const emailError = document.getElementById('emailError');

form.addEventListener('submit', function(e) {
    const email = emailInput.value.trim();
    
    if (email === '') {
        e.preventDefault();
        emailError.textContent = 'Email is required.';
        emailError.style.display = 'block';
        emailInput.style.border = '1px solid #dc3545';
        return false;
    }
    
    if (!validateEmail(email)) {
        e.preventDefault();
        emailError.textContent = 'Please enter a valid email address.';
        emailError.style.display = 'block';
        emailInput.style.border = '1px solid #dc3545';
        return false;
    }
    
    emailError.style.display = 'none';
    emailInput.style.border = '1px solid #ccc';
    return true;
});

// Clear error on input
emailInput.addEventListener('input', function() {
    if (validateEmail(this.value)) {
        emailError.style.display = 'none';
        this.style.border = '1px solid #ccc';
    }
});
</script>

</body>
</html>