<?php
require_once("includes/security.php");
secureSessionStart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bloodline Home</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <img src="assets/images/logo.png" alt="logo">
        </div>
        <h1 class="auth-title">Bloodline Home</h1>

        <form action="auth/register-handler.php" method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>

            <input type="hidden" name="role" id="role" value="user">

            <div id="adminFields" class="form-grid" style="display:none; margin-bottom:15px;">
                <div class="form-group">
                    <label>Hospital / Organization Name:</label>
                    <input type="text" name="hospital_name" id="hospitalName" placeholder="Enter hospital or organization name">
                </div>
            </div>

            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Full name:</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="regPassword" required placeholder="" autocomplete="new-password" style="width:100%;padding-right:100px;">
                        <button type="button" onclick="togglePwd('regPassword')"
                            style="position:absolute;right:50px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;line-height:1;color:#888;">
                            <svg id="eyeIcon-regPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <button type="button" onclick="showPasswordGenerator()" title="Generate Password"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:#C2185B;color:white;border:none;cursor:pointer;padding:6px 10px;border-radius:4px;font-size:14px;font-weight:700;line-height:1;">
                            ⚡
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password:</label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="regConfirmPassword" required placeholder="Re-enter your password" autocomplete="new-password" style="width:100%;padding-right:42px;">
                        <button type="button" onclick="togglePwd('regConfirmPassword')"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;line-height:1;color:#888;">
                            <svg id="eyeIcon-regConfirmPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div id="userFields">
                <div class="divider">
                    <h3>Donor Information:</h3>
                </div>

                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; font-weight: 500; pointer-events: none;">+977</span>
                            <input type="text" name="phone" id="phoneInput" placeholder="10-digit number (e.g., 9841234567)" style="padding-left: 55px;" inputmode="numeric">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Blood Group:</label>
                        <select name="blood_group">
                            <option value="">Select Blood Group</option>
                            <?php
                            $groups = [
                                'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-',
                                'A1+', 'A1-', 'A2+', 'A2-', 'A1B+', 'A1B-', 'A2B+', 'A2B-',
                                'Bombay (Oh)', 'Rh-null'
                            ];
                            foreach ($groups as $group):
                            ?>
                                <option value="<?php echo htmlspecialchars($group); ?>">
                                    <?php echo htmlspecialchars($group); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Age:</label>
                        <input type="number" name="age" placeholder="18 or 18+">
                    </div>

                    <div class="form-group">
                        <label>Weight:</label>
                        <input type="number" step="0.01" name="weight" placeholder="Minimum 45 kg">
                    </div>

                    <div class="form-group">
                        <label>Gender:</label>
                        <div class="gender-row">
                            <label><input type="radio" name="gender" value="Male"> Male</label>
                            <label><input type="radio" name="gender" value="Female"> Female</label>
                            <label><input type="radio" name="gender" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Health Card Image:</label>
                        <input type="file" name="health_card" accept="image/*" placeholder="Upload health card image">
                        <small style="color: #666; margin-top: 5px; display: block;">Accepted formats: JPG, PNG, GIF, WebP (Max 5MB)</small>
                    </div>

                    <div class="form-group">
                        <label>Address:</label>
                        <input type="text" name="address">
                    </div>

                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" name="city">
                    </div>

                    <div class="form-group">
                        <label>Province:</label>
                        <input type="text" name="province" required>
                    </div>

                    <div class="form-group">
                        <label>Zip Code:</label>
                        <input type="text" name="zip_code">
                    </div>

                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Emergency Contact Emails (Required - 3):</label>
                        <input type="email" name="emergency_email_1" placeholder="Emergency Contact Email 1" required>
                        <input type="email" name="emergency_email_2" placeholder="Emergency Contact Email 2" required style="margin-top: 10px;">
                        <input type="email" name="emergency_email_3" placeholder="Emergency Contact Email 3" required style="margin-top: 10px;">
                    </div>

                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Medical History:</label>
                        <textarea name="medical_history"></textarea>
                    </div>
                </div>
            </div>

            <button class="auth-btn" type="submit">Sign up</button>

            <?php if (isset($_SESSION['registration_success'])): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-top: 15px; border: 1px solid #c3e6cb; text-align: center;">
                    <?php 
                    echo htmlspecialchars($_SESSION['registration_success']);
                    unset($_SESSION['registration_success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['registration_error'])): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 4px; margin-top: 15px; border: 1px solid #f5c6cb; text-align: center;">
                    <?php 
                    echo htmlspecialchars($_SESSION['registration_error']);
                    unset($_SESSION['registration_error']);
                    ?>
                </div>
            <?php endif; ?>

            <div id="successMessage" style="display:none; background-color: #d4edda; color: #155724; padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; text-align: center; margin-top: 20px;"></div>

            <div class="auth-link">
                Already Have an Account? <a href="login.php">Log in</a>
            </div>
        </form>
    </div>
</div>

<!-- Password Generator Modal -->
<div id="passwordGeneratorModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:2000;justify-content:center;align-items:center;">
    <div style="background:white;border-radius:12px;padding:30px;width:90%;max-width:450px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;font-size:22px;font-weight:700;color:#333;">Password Generator</h2>
            <button onclick="closePasswordGenerator()" style="background:none;border:none;font-size:28px;cursor:pointer;color:#999;">&times;</button>
        </div>
        
        <div style="background:#f5f5f5;padding:15px;border-radius:8px;margin-bottom:20px;border:2px dashed #ddd;">
            <p style="margin:0 0 10px 0;font-size:12px;color:#666;font-weight:700;">GENERATED PASSWORD:</p>
            <div style="display:flex;gap:10px;align-items:center;">
                <input type="text" id="generatedPassword" readonly style="flex:1;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-weight:600;font-family:monospace;background:white;" value="">
                <button type="button" onclick="copyGeneratedPassword()" style="background:#940404;color:white;border:none;padding:12px 16px;border-radius:6px;cursor:pointer;font-weight:700;font-size:12px;">COPY</button>
            </div>
        </div>
        
        <div style="margin-bottom:20px;">
            <button type="button" onclick="generateRandomPassword()" style="width:100%;background:#940404;color:white;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:700;font-size:14px;margin-bottom:10px;">🔄 Generate New Password</button>
            <button type="button" onclick="useGeneratedPassword()" style="width:100%;background:#4caf50;color:white;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:700;font-size:14px;margin-bottom:10px;">✓ Use This Password</button>
        </div>
        
        <div style="border-top:1px solid #eee;padding-top:15px;">
            <p style="margin:0 0 15px 0;font-size:14px;font-weight:700;color:#333;">Or Create Your Own:</p>
            <div style="margin-bottom:10px;">
                <input type="password" id="customPassword" placeholder="Enter your own password" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px;box-sizing:border-box;" onfocus="this.style.borderColor='#940404'" onblur="this.style.borderColor='#ddd'">
            </div>
            <p style="margin:5px 0 0 0;font-size:12px;color:#666;">Requirements: Min 8 characters, 1 special char (!@#$%^&*...), letters/numbers</p>
            <button type="button" onclick="useCustomPassword()" style="width:100%;background:#2196F3;color:white;border:none;padding:12px;border-radius:6px;cursor:pointer;font-weight:700;font-size:14px;margin-top:10px;">Use Custom Password</button>
        </div>
    </div>
</div>

<script>
function showPasswordGenerator() {
    generateRandomPassword();
    document.getElementById('passwordGeneratorModal').style.display = 'flex';
}

function closePasswordGenerator() {
    document.getElementById('passwordGeneratorModal').style.display = 'none';
}

function generateRandomPassword() {
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const lowercase = 'abcdefghijklmnopqrstuvwxyz';
    const numbers = '0123456789';
    const special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    const allChars = uppercase + lowercase + numbers + special;
    
    let password = '';
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += special[Math.floor(Math.random() * special.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    
    for (let i = 3; i < 12; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
    }
    
    password = password.split('').sort(() => Math.random() - 0.5).join('');
    document.getElementById('generatedPassword').value = password;
}

function copyGeneratedPassword() {
    const password = document.getElementById('generatedPassword').value;
    navigator.clipboard.writeText(password).then(() => {
        alert('Password copied to clipboard!');
    });
}

function useGeneratedPassword() {
    const password = document.getElementById('generatedPassword').value;
    document.getElementById('regPassword').value = password;
    document.getElementById('regConfirmPassword').value = password;
    closePasswordGenerator();
}

function useCustomPassword() {
    const password = document.getElementById('customPassword').value;
    const passwordRegex = /^(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/;
    
    if (password === '') {
        alert('Please enter a password.');
        return;
    }
    
    if (!passwordRegex.test(password)) {
        alert('Password must be at least 8 characters long, contain at least 1 special character, and include letters or numbers.');
        return;
    }
    
    document.getElementById('regPassword').value = password;
    document.getElementById('regConfirmPassword').value = password;
    closePasswordGenerator();
}

function togglePwd(inputId) {
    var input = document.getElementById(inputId);
    var icon  = document.getElementById('eyeIcon-' + inputId);
    if (input.type === 'password') {
        input.type = 'text';
        // Eye-off icon (password hidden)
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.08-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
    } else {
        input.type = 'password';
        // Eye icon (password visible)
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }
}

function setRole(role, el) {
    // Role is now always 'user' - admin registration is disabled
    document.getElementById('role').value = 'user';
    document.getElementById('adminFields').style.display = 'none';
    document.getElementById('userFields').style.display = 'block';
    const phoneInput = document.getElementById('phoneInput');
    const hospitalNameInput = document.getElementById('hospitalName');
    phoneInput.setAttribute('required', 'required');
    hospitalNameInput.removeAttribute('required');
}

function formatPhoneInput(input) {
    // Remove any non-digit characters
    let value = input.value.replace(/\D/g, '');
    // Keep only 10 digits
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    input.value = value;
}

function validateRegisterForm(form) {
    const password        = document.getElementById('regPassword').value;
    const confirmPassword = document.getElementById('regConfirmPassword').value;
    const phone           = form.phone ? form.phone.value.replace(/\D/g, '') : ''; // Extract only digits
    const age             = form.age  ? form.age.value  : '';
    const role            = document.getElementById('role').value;
    const emergencyEmail1 = form.emergency_email_1 ? form.emergency_email_1.value.trim().toLowerCase() : '';
    const emergencyEmail2 = form.emergency_email_2 ? form.emergency_email_2.value.trim().toLowerCase() : '';
    const emergencyEmail3 = form.emergency_email_3 ? form.emergency_email_3.value.trim().toLowerCase() : '';

    // Password: min 8 chars, at least 1 special character, letters or numbers
    const passwordRegex = /^(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?])(?=.*[a-zA-Z0-9]).{8,}$/;
    if (!passwordRegex.test(password)) {
        alert('Password must be at least 8 characters long, contain at least 1 special character (!@#$%^&*...), and include letters or numbers.');
        return false;
    }

    if (password !== confirmPassword) {
        alert('Passwords do not match.');
        return false;
    }

    if (role === 'admin') {
        const hospitalName = document.getElementById('hospitalName').value;
        if (!hospitalName.trim()) {
            alert('Hospital name is required for admin registration.');
            return false;
        }
    }

    if (role === 'user') {
        const phoneRegex = /^[0-9]{10}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!phoneRegex.test(phone)) {
            alert('Phone number must be exactly 10 digits. No minus sign (-) allowed.');
            return false;
        }
        if (!emailRegex.test(emergencyEmail1) || !emailRegex.test(emergencyEmail2) || !emailRegex.test(emergencyEmail3)) {
            alert('Please enter 3 valid emergency contact emails.');
            return false;
        }
        if (new Set([emergencyEmail1, emergencyEmail2, emergencyEmail3]).size !== 3) {
            alert('Emergency contact emails must be different from each other.');
            return false;
        }
        if (!age || parseInt(age) < 18) {
            alert('You must be at least 18 years old to be a donor.');
            return false;
        }
    }

    return true;
}

const form = document.querySelector('form');
form.addEventListener('submit', function(e) {
    if (!validateRegisterForm(form)) {
        e.preventDefault();
        return false;
    }
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Signing up...';
    }
});

// Phone input formatting
const phoneInput = document.getElementById('phoneInput');
if (phoneInput) {
    phoneInput.addEventListener('input', function() {
        formatPhoneInput(this);
    });
    phoneInput.addEventListener('keypress', function(e) {
        // Allow only numeric input
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });
    phoneInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const cleanText = pastedText.replace(/\D/g, '').slice(0, 10);
        this.value = cleanText;
    });
}

// Clear sensitive fields on page load to prevent browser autofill
window.addEventListener('load', function() {
    document.querySelector('input[name="email"]').value = '';
    document.getElementById('regPassword').value = '';
    document.getElementById('regConfirmPassword').value = '';
});
</script>

</body>
</html>