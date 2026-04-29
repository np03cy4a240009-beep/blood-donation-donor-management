<?php
require_once("../includes/security.php");
secureSessionStart();

// Check if user has initiated registration with OTP
if (!isset($_SESSION['register_email']) || !isset($_SESSION['register_otp'])) {
    header("Location: ../register.php");
    exit();
}

$demoOtp = $_SESSION['register_otp'];
$emailForDisplay = substr($_SESSION['register_email'], 0, 3) . '***' . substr($_SESSION['register_email'], -4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Bloodline Home</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .otp-demo-box {
            background-color: #f0f8ff;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .otp-demo-box h4 {
            margin: 0 0 10px 0;
            color: #dc3545;
            font-size: 14px;
        }
        .otp-demo-code {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
            letter-spacing: 3px;
            font-family: monospace;
        }
        .otp-timer {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        .timer-value {
            font-weight: bold;
            color: #dc3545;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card small">
        <div class="auth-logo">
            <img src="../assets/images/logo.png" alt="logo">
        </div>
        <h1 class="auth-title">Bloodline Home</h1>

        <p style="text-align: center; color: #666; margin-bottom: 15px;">
            Enter your OTP for verification:
        </p>

        <!-- Demo OTP Display Box -->
        <div class="otp-demo-box">
            <h4>Your OTP</h4>
            <div class="otp-demo-code" id="demoOtp"><?php echo htmlspecialchars($demoOtp); ?></div>
            <div class="otp-timer">
                Valid for <span class="timer-value"><span id="timer">60</span>s</span>
            </div>
        </div>

        <form action="verify-otp-register-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>OTP:</label>
                <input type="text" name="otp" id="otpInput" required maxlength="6" placeholder="000000" autocomplete="off" inputmode="numeric">
            </div>

            <button class="auth-btn" type="submit" id="verifyBtn">Verify</button>
            <button class="auth-btn" type="button" style="background-color: #6c757d; margin-top: 10px;" onclick="cancelRegistration()">Cancel</button>

            <?php if (isset($_SESSION['otp_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-top: 12px; border: 1px solid #f5c6cb; text-align: center;">
                    <?php 
                    echo htmlspecialchars($_SESSION['otp_error']);
                    unset($_SESSION['otp_error']);
                    ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
// Clear OTP field on page load
window.addEventListener('load', function() {
    document.getElementById('otpInput').value = '';
    startTimer();
});

// Only allow numeric input
document.getElementById('otpInput').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^0-9]/g, '');
});

// Timer countdown
function startTimer() {
    let timeLeft = 60;
    const timerElement = document.getElementById('timer');
    const verifyBtn = document.getElementById('verifyBtn');
    
    const interval = setInterval(function() {
        timeLeft--;
        timerElement.textContent = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(interval);
            verifyBtn.disabled = true;
            verifyBtn.style.opacity = '0.5';
            // Automatically request new OTP when timer expires
            setTimeout(function() {
                window.location.href = '../auth/resend-otp-register.php';
            }, 1000);
        }
    }, 1000);
}

// Cancel registration
function cancelRegistration() {
    if (confirm('Are you sure you want to cancel registration?')) {
        window.location.href = '../register.php';
    }
}
</script>

</body>
</html>
