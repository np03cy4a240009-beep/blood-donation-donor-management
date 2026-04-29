<?php
require_once("../includes/security.php");
secureSessionStart();

// Check if user has initiated OTP login
if (!isset($_SESSION['otp_login_email'])) {
    header("Location: ../login.php");
    exit();
}

$loginEmail = $_SESSION['otp_login_email'];
$demoOtp = $_SESSION['otp_demo'] ?? '';  // Get the actual working OTP
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Bloodline Home</title>
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
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
            letter-spacing: 4px;
            font-family: monospace;
            margin: 10px 0;
        }
        .otp-timer {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        .timer-value {
            font-weight: bold;
            color: #dc3545;
            font-size: 16px;
        }
        .email-info {
            background-color: #e8f4f8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 13px;
            color: #555;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card small">
        <div class="auth-logo">
            <img src="../assets/images/logo.png" alt="logo">
        </div>
        <h1 class="auth-title">Verify OTP</h1>

        <div class="email-info">
            OTP sent to: <strong><?php echo htmlspecialchars($loginEmail); ?></strong>
        </div>

        <!-- Demo OTP Display Box - This is the actual working OTP -->
        <div class="otp-demo-box">
            <h4>Your OTP</h4>
            <div class="otp-demo-code" id="demoOtp"><?php echo htmlspecialchars($demoOtp); ?></div>
            <div class="otp-timer">
                Valid for <span class="timer-value"><span id="timer">60</span>s</span>
            </div>
        </div>

        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Enter the 6-digit OTP above in the field below to verify your login.
        </p>

        <form action="verify-otp-login-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>One-Time Password (OTP):</label>
                <input type="text" name="otp" id="otpInput" required maxlength="6" placeholder="000000" autocomplete="off" inputmode="numeric">
            </div>

            <button class="auth-btn" type="submit" id="verifyBtn">Verify OTP</button>

            <?php if (isset($_SESSION['otp_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-top: 12px; border: 1px solid #f5c6cb; text-align: center;">
                    <?php 
                    echo htmlspecialchars($_SESSION['otp_error']);
                    unset($_SESSION['otp_error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="auth-link" style="margin-top: 15px;">
                <a href="../login.php">Back to Login</a>
            </div>
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

// Timer countdown (1 minute = 60 seconds)
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
                window.location.href = '../auth/resend-otp.php';
            }, 1000);
        }
    }, 1000);
}
</script>

</body>
</html>
