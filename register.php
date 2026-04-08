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

        <form action="auth/register-handler.php" method="POST">
            <?php echo csrfField(); ?>

            <div class="form-group">
                <label>Register As:</label>
            </div>

            <div class="role-toggle">
                <div class="role-box active" onclick="setRole('user', this)">User</div>
                <div class="role-box" onclick="setRole('admin', this)">Admin</div>
            </div>

            <input type="hidden" name="role" id="role" value="user">

            <div id="adminFields" class="form-grid" style="display:none; margin-bottom:15px;">
                <div class="form-group full">
                    <label>Hospital Name:</label>
                    <input type="text" name="hospital_name">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Full name:</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" name="confirm_password" required>
                </div>
            </div>

            <div id="userFields">
                <div class="divider">
                    <h3>Donor Information:</h3>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <input type="text" name="phone">
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
                        <input type="number" name="age">
                    </div>

                    <div class="form-group">
                        <label>Weight:</label>
                        <input type="number" step="0.01" name="weight">
                    </div>

                    <div class="form-group full">
                        <label>Gender:</label>
                        <div class="gender-row">
                            <label><input type="radio" name="gender" value="Male"> Male</label>
                            <label><input type="radio" name="gender" value="Female"> Female</label>
                            <label><input type="radio" name="gender" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label>Address:</label>
                        <input type="text" name="address">
                    </div>

                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" name="city">
                    </div>

                    <div class="form-group">
                        <label>State:</label>
                        <input type="text" name="state">
                    </div>

                    <div class="form-group">
                        <label>Zip Code:</label>
                        <input type="text" name="zip_code">
                    </div>

                    <div class="form-group full">
                        <label>Medical History:</label>
                        <textarea name="medical_history"></textarea>
                    </div>
                </div>
            </div>

            <button class="auth-btn" type="submit">Sign up</button>

            <div class="auth-link">
                Already Have an Account? <a href="login.php">Log in</a>
            </div>
        </form>
    </div>
</div>

<script>
function setRole(role, el) {
    document.getElementById('role').value = role;
    document.querySelectorAll('.role-box').forEach(box => box.classList.remove('active'));
    el.classList.add('active');

    if (role === 'admin') {
        document.getElementById('adminFields').style.display = 'grid';
        document.getElementById('userFields').style.display = 'none';
    } else {
        document.getElementById('adminFields').style.display = 'none';
        document.getElementById('userFields').style.display = 'block';
    }
}
</script>

</body>
</html>