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

        <?php if (isset($_SESSION['otp_success'])): ?>
            <div style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['otp_success']);
                unset($_SESSION['otp_success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['reset_error'])): ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center;">
                <?php 
                echo htmlspecialchars($_SESSION['reset_error']);
                unset($_SESSION['reset_error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['otp_demo'])): ?>
            <div class="otp-demo">Demo OTP: <?php echo htmlspecialchars($_SESSION['otp_demo']); ?> (Valid for 1 minute)</div>
        <?php endif; ?>

        <form action="auth/reset-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>OTP: <span style="color: #dc3545; font-size: 12px;">(Expires in 1 minute)</span></label>
                <input type="text" id="otp" name="otp" required placeholder="Enter 6-digit OTP">
                <div id="otpError" style="color: #dc3545; font-size: 12px; margin-top: 5px; display: none;"></div>
            </div>

            <div class="form-group" style="margin-top:14px;">
                <label>New password:</label>
                <input type="password" id="password" name="new_password" required>
                <div id="passwordError" style="color: #dc3545; font-size: 12px; margin-top: 5px; display: none;"></div>
            </div>

            <button class="auth-btn" type="submit">Change password</button>
            <a href="login.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;">Cancel</a>
        </form>
    </div>
</div>

<script>
function validateResetForm(form) {
    const otp = form.otp.value.trim();
    const password = form.new_password.value;
    const otpInput = document.getElementById('otp');
    const passwordInput = document.getElementById('password');
    const otpError = document.getElementById('otpError');
    const passwordError = document.getElementById('passwordError');
    let isValid = true;

    // OTP validation: must be 6 digits
    const otpRegex = /^[0-9]{6}$/;
    if (otp === '') {
        otpError.textContent = 'OTP is required.';
        otpError.style.display = 'block';
        otpInput.style.border = '1px solid #dc3545';
        isValid = false;
    } else if (!otpRegex.test(otp)) {
        otpError.textContent = 'OTP must be exactly 6 digits. Please enter a valid OTP.';
        otpError.style.display = 'block';
        otpInput.style.border = '1px solid #dc3545';
        isValid = false;
    } else {
        otpError.style.display = 'none';
        otpInput.style.border = '1px solid #ccc';
    }

    // Password validation: min 8 chars, at least 1 special character, letters or numbers
    const passwordRegex = /^(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/;
    if (password === '') {
        passwordError.textContent = 'Password is required.';
        passwordError.style.display = 'block';
        passwordInput.style.border = '1px solid #dc3545';
        isValid = false;
    } else if (!passwordRegex.test(password)) {
        passwordError.textContent = 'Password must be 8+ characters, with 1 special character (!@#$%^&*...) and letters or numbers.';
        passwordError.style.display = 'block';
        passwordInput.style.border = '1px solid #dc3545';
        isValid = false;
    } else {
        passwordError.style.display = 'none';
        passwordInput.style.border = '1px solid #ccc';
    }

    return isValid;
}

const form = document.querySelector('form');
const otpInput = document.getElementById('otp');
const passwordInput = document.getElementById('password');
const otpError = document.getElementById('otpError');
const passwordError = document.getElementById('passwordError');

// OTP validation on input
otpInput.addEventListener('input', function() {
    const otpRegex = /^[0-9]{6}$/;
    if (otpRegex.test(this.value)) {
        otpError.style.display = 'none';
        this.style.border = '1px solid #ccc';
    }
});

// Password validation on input
passwordInput.addEventListener('input', function() {
    const passwordRegex = /^(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/;
    if (passwordRegex.test(this.value)) {
        passwordError.style.display = 'none';
        this.style.border = '1px solid #ccc';
    }
});

form.addEventListener('submit', function(e) {
    if (!validateResetForm(form)) {
        e.preventDefault();
        return false;
    }
});
</script>

</body>
</html>