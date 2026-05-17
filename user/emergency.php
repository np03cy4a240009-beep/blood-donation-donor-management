<?php
include("../config/user-session.php");
include("../config/db.php");
include("../config/env-loader.php");
include("../includes/functions.php");
include("../auth/send-otp-mail.php");
require_once("../includes/security.php");

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';
$bloodAvailability = [];
$nearbyBloodAvailability = [];

try {
    // Get user's location and age
    $userStmt = $conn->prepare("SELECT full_name, address, city, province, blood_group, age, emergency_email_1, emergency_email_2, emergency_email_3 FROM users WHERE id = ?");
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userData = $userResult->fetch_assoc();
} catch (Exception $e) {
    $message = "Error loading user data";
    $messageType = "error";
    $userData = [];
}

$userCity = $userData['city'] ?? 'Unknown';
$userProvince = $userData['province'] ?? 'Unknown';
$userBloodGroup = $userData['blood_group'] ?? 'O+';

// Get available blood in inventory in same city
try {
    $inventoryStmt = $conn->prepare("
        SELECT bi.blood_type, COUNT(bi.id) as quantity, u.city, u.address
        FROM blood_inventory bi
        JOIN users u ON bi.donor_id = u.id
        WHERE bi.status = 'Available' 
        AND bi.screening_status = 'safe'
        AND DATE(bi.expiry_date) >= CURDATE()
        AND u.city = ?
        GROUP BY bi.blood_type, u.city, u.address
        ORDER BY bi.blood_type
    ");
    $inventoryStmt->bind_param("s", $userCity);
    $inventoryStmt->execute();
    $inventory = $inventoryStmt->get_result();
} catch (Exception $e) {
    $inventory = null;
}

if ($inventory) {
    while($row = $inventory->fetch_assoc()) {
        $bloodType = $row['blood_type'];
        if (!isset($bloodAvailability[$bloodType])) {
            $bloodAvailability[$bloodType] = ['quantity' => 0, 'location' => []];
        }
        $bloodAvailability[$bloodType]['quantity'] += $row['quantity'];
        if (!in_array($row['address'], $bloodAvailability[$bloodType]['location'])) {
            $bloodAvailability[$bloodType]['location'][] = $row['address'];
        }
    }
}

// Get available blood from nearby cities/provinces if not in same city
try {
    $nearbyStmt = $conn->prepare("
        SELECT bi.blood_type, COUNT(bi.id) as quantity, u.city, u.address
        FROM blood_inventory bi
        JOIN users u ON bi.donor_id = u.id
        WHERE bi.status = 'Available' 
        AND bi.screening_status = 'safe'
        AND DATE(bi.expiry_date) >= CURDATE()
        AND u.city != ?
        AND (u.province = ? OR u.city LIKE ?)
        GROUP BY bi.blood_type, u.city, u.address
        ORDER BY u.city, bi.blood_type
    ");
    $searchProvincePattern = "%{$userProvince}%";
    $nearbyStmt->bind_param("sss", $userCity, $userProvince, $searchProvincePattern);
    $nearbyStmt->execute();
    $nearbyInventory = $nearbyStmt->get_result();
} catch (Exception $e) {
    $nearbyInventory = null;
}

if ($nearbyInventory) {
    while($row = $nearbyInventory->fetch_assoc()) {
        $bloodType = $row['blood_type'];
        if (!isset($nearbyBloodAvailability[$bloodType])) {
            $nearbyBloodAvailability[$bloodType] = ['quantity' => 0, 'location' => []];
        }
        $nearbyBloodAvailability[$bloodType]['quantity'] += $row['quantity'];
        $location = $row['address'] . ' (' . $row['city'] . ')';
        if (!in_array($location, $nearbyBloodAvailability[$bloodType]['location'])) {
            $nearbyBloodAvailability[$bloodType]['location'][] = $location;
        }
    }
}

// Handle Quick SOS button click - sends emergency alert immediately
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_sos'])) {
    if (!csrfCheck()) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    } else {
        try {
            // Get emergency contact emails
            $contactEmails = array_filter([
                $userData['emergency_email_1'] ?? '',
                $userData['emergency_email_2'] ?? '',
                $userData['emergency_email_3'] ?? ''
            ]);

            if (empty($contactEmails)) {
                $message = "❌ No emergency contact emails registered. Please update your profile with 3 emergency contact emails first.";
                $messageType = "error";
            } else {
                $alertSentCount = 0;
                $alertFailCount = 0;

                // Send emergency alert to each contact
                foreach ($contactEmails as $contactEmail) {
                    if (isValidEmail($contactEmail)) {
                        if (sendEmergencyAlertMail($contactEmail, $userData['full_name'] ?? 'User')) {
                            $alertSentCount++;
                            error_log("✓ Emergency alert SENT to: $contactEmail");
                        } else {
                            $alertFailCount++;
                            error_log("✗ Emergency alert FAILED to: $contactEmail");
                        }
                    } else {
                        $alertFailCount++;
                        error_log("✗ Invalid email: $contactEmail");
                    }
                }

                // Show result message
                if ($alertSentCount > 0) {
                    $message = "✓ EMERGENCY ALERT SENT! Emergency message delivered to " . $alertSentCount . " contact(s). ";
                    if ($alertFailCount > 0) {
                        $message .= "(" . $alertFailCount . " failed)";
                    }
                    $message .= " Fill in details below to create a blood request record.";
                    $messageType = "success";
                } else {
                    $message = "❌ Failed to send emergency alerts to all contacts. Please try again or contact admin.";
                    $messageType = "error";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . htmlspecialchars($e->getMessage());
            $messageType = "error";
            error_log("Quick SOS error: " . $e->getMessage());
        }
    }
}

// Handle detailed SOS request form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sos_request'])) {
    // Verify CSRF token
    if (!csrfCheck()) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    } else {
        try {
            $userAge = (int)($userData['age'] ?? 0);
            $bloodNeeded = trim($_POST['blood_needed'] ?? $userBloodGroup);
            $unitsNeeded = (int)($_POST['units_needed'] ?? 2);
            $emergencyReason = trim($_POST['emergency_reason'] ?? '');
            
            if ($bloodNeeded === '' || $unitsNeeded <= 0) {
                $message = "Please specify blood type and units needed.";
                $messageType = "error";
            } else {
                // ALWAYS create emergency request - record the data even if blood not available
                $request_id = "EMRG" . time() . rand(10, 99);
                $urgency = 'Urgent';
                $status = 'pending';
                $request_date = date('Y-m-d');
                $required_by = date('Y-m-d');
                
                $userPhone = $_SESSION['email'] ?? 'Emergency Contact';
                $userLocation = $userData['address'] ?? $userCity;
                
                $emergencyStmt = $conn->prepare("
                    INSERT INTO blood_requests 
                    (request_id, user_id, contact, location, hospital_name, blood_type, units, urgency, status, request_date, required_by, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if (!$emergencyStmt) {
                    throw new Exception("Prepare error: " . $conn->error);
                }
                
                $hospitalName = 'Bir Hospital'; // Default hospital
                $emergencyStmt->bind_param(
                    "sissssisssss",
                    $request_id,
                    $user_id,
                    $userPhone,
                    $userLocation,
                    $hospitalName,
                    $bloodNeeded,
                    $unitsNeeded,
                    $urgency,
                    $status,
                    $request_date,
                    $required_by,
                    $emergencyReason
                );
                
                if (!$emergencyStmt->execute()) {
                    throw new Exception("Execute error: " . $emergencyStmt->error);
                }

                // Notify emergency contacts registered during signup
                $contactEmails = array_filter([
                    $userData['emergency_email_1'] ?? '',
                    $userData['emergency_email_2'] ?? '',
                    $userData['emergency_email_3'] ?? ''
                ]);
                $alertSentCount = 0;
                $alertFailCount = 0;

                foreach ($contactEmails as $contactEmail) {
                    if (isValidEmail($contactEmail) && sendEmergencyAlertMail($contactEmail, $userData['full_name'] ?? 'User')) {
                        $alertSentCount++;
                    } else {
                        $alertFailCount++;
                    }
                }
                
                // Check if blood is available to determine message type
                $bloodAvailableLocally = isset($bloodAvailability[$bloodNeeded]) && $bloodAvailability[$bloodNeeded]['quantity'] > 0;
                $bloodAvailableNearby = isset($nearbyBloodAvailability[$bloodNeeded]) && $nearbyBloodAvailability[$bloodNeeded]['quantity'] > 0;
                
                if ($bloodAvailableLocally || $bloodAvailableNearby) {
                    // Blood available - show success
                    $locationInfo = '';
                    if ($bloodAvailableLocally) {
                        $locationInfo = " Available in your city: " . htmlspecialchars(implode(', ', $bloodAvailability[$bloodNeeded]['location']));
                    } else {
                        $locationInfo = " Available at: " . htmlspecialchars(implode(', ', $nearbyBloodAvailability[$bloodNeeded]['location']));
                    }
                    $message = "✓ Emergency SOS sent! Admins have been notified.$locationInfo";
                    if ($alertSentCount > 0) {
                        $message .= " Emergency alert email sent to {$alertSentCount} contact(s).";
                    }
                    if ($alertFailCount > 0) {
                        $message .= " {$alertFailCount} contact email(s) could not be sent.";
                    }
                    $messageType = "success";
                } else {
                    // No blood available - show warning but request is recorded
                    $compatibleTypes = getCompatibleBloodTypes($bloodNeeded);
                    $suggestedTypes = [];
                    
                    foreach ($compatibleTypes as $compatType) {
                        if ($compatType !== $bloodNeeded) {
                            if (isset($bloodAvailability[$compatType]) && $bloodAvailability[$compatType]['quantity'] > 0) {
                                $suggestedTypes[] = $compatType . ' (' . $bloodAvailability[$compatType]['quantity'] . ' units available in ' . htmlspecialchars(implode(', ', $bloodAvailability[$compatType]['location'])) . ')';
                            } elseif (isset($nearbyBloodAvailability[$compatType]) && $nearbyBloodAvailability[$compatType]['quantity'] > 0) {
                                $suggestedTypes[] = $compatType . ' (Available at: ' . htmlspecialchars(implode(', ', $nearbyBloodAvailability[$compatType]['location'])) . ')';
                            }
                        }
                    }
                    
                    $message = "✓ Emergency SOS recorded! Admins have been notified. ⚠️ No " . htmlspecialchars($bloodNeeded) . " blood available in your area. ";
                    if (!empty($suggestedTypes)) {
                        $message .= "Compatible blood types available: " . implode(' | ', $suggestedTypes);
                    } else {
                        $message .= "Please contact the nearest blood bank for alternatives. Admins will follow up.";
                    }
                    if ($alertSentCount > 0) {
                        $message .= " Emergency alert email sent to {$alertSentCount} contact(s).";
                    }
                    if ($alertFailCount > 0) {
                        $message .= " {$alertFailCount} contact email(s) could not be sent.";
                    }
                    $messageType = "warning";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . htmlspecialchars($e->getMessage());
            $messageType = "error";
            error_log("Emergency request error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php include("../includes/theme-head.php"); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency - Bloodline Home</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .emergency-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px;
        }

        .blood-availability {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .blood-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .blood-type {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .blood-quantity {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
        }
        .blood-location {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
        }
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            border: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .submit-btn {
            background: #dc3545;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background: #b13232;
        }
        .message-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .message-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .message-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }
        .sos-quick-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            background: #8b0000;
            color: #fff;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: none;
            text-decoration: none;
            font-weight: 700;
            font-size: 22px;
            margin: 0 auto 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(139, 0, 0, 0.3);
        }
        .sos-quick-btn i {
            font-size: 30px;
        }
        .sos-quick-btn:hover {
            background: #6f0000;
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(139, 0, 0, 0.5);
        }
        .sos-quick-btn:active {
            transform: scale(0.98);
        }
        .location-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Dark mode — darker panels, readable labels & body text */
        [data-theme="dark"] .emergency-container > p {
            color: var(--text-secondary);
        }
        [data-theme="dark"] .location-info {
            background: #1a2838;
            border-left-color: #5a9fd4;
            color: var(--body-text);
        }
        [data-theme="dark"] .location-info h3 {
            color: var(--heading-color);
        }
        [data-theme="dark"] .location-info p,
        [data-theme="dark"] .location-info strong {
            color: var(--body-text);
        }
        [data-theme="dark"] .blood-availability {
            background: var(--bg-panel);
            border: 1px solid var(--border-soft);
        }
        [data-theme="dark"] .blood-availability h2 {
            color: var(--heading-color) !important;
        }
        [data-theme="dark"] .blood-availability > p {
            color: var(--text-secondary) !important;
        }
        [data-theme="dark"] .blood-card {
            background: var(--bg-card);
            border: 1px solid var(--border-soft);
        }
        [data-theme="dark"] .blood-type {
            color: var(--body-text);
        }
        [data-theme="dark"] .blood-type[style*="color: #007bff"],
        [data-theme="dark"] .blood-type[style*="color:#007bff"] {
            color: #7eb8ff !important;
        }
        [data-theme="dark"] .blood-location {
            color: var(--text-secondary);
        }
        [data-theme="dark"] .blood-availability h2[style*="color: #007bff"],
        [data-theme="dark"] .blood-availability h2[style*="color:#007bff"] {
            color: #7eb8ff !important;
        }
        [data-theme="dark"] .form-section {
            background: var(--bg-card);
            border-color: var(--border-soft);
        }
        [data-theme="dark"] .form-section h2 {
            color: var(--heading-color);
        }
        [data-theme="dark"] .form-group label {
            color: var(--body-text);
        }
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select,
        [data-theme="dark"] .form-group textarea {
            background: var(--input-bg);
            color: var(--input-text);
            border-color: var(--border-soft);
        }
        [data-theme="dark"] .form-group input::placeholder,
        [data-theme="dark"] .form-group textarea::placeholder {
            color: var(--text-muted);
        }
        [data-theme="dark"] .message-success {
            background: #1a2e22;
            color: #8fd99a;
            border-color: #2d5a3a;
        }
        [data-theme="dark"] .message-error {
            background: #2e1a1a;
            color: #f5a5a5;
            border-color: #5a3030;
        }
        [data-theme="dark"] .message-warning {
            background: #2e2618;
            color: #f0d080;
            border-color: #5a4a28;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .blood-card {
                flex-direction: column;
                align-items: flex-start;
            }
            .blood-quantity {
                margin-top: 10px;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        
        <div class="emergency-container">
            <h1 style="color: #dc3545; margin-bottom: 10px;">Emergency Blood Request</h1>
            <p style="color: #666; margin-bottom: 30px;">Use this feature in case of urgent medical emergency. Admins will be notified immediately with available blood information.</p>

            <?php if ($message): ?>
                <div class="message-box message-<?php echo htmlspecialchars($messageType); ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="sosForm" method="POST" style="display: flex; justify-content: center; margin: 20px 0;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="quick_sos" value="1">
                <button type="button" class="sos-quick-btn" title="Click to send emergency alert to your 3 emergency contacts" onclick="showSosTermsModal(event)">
                    <i class="ph-thin ph-siren"></i>
                    SOS
                </button>
            </form>

            <div class="location-info">
                <h3 style="margin: 0 0 10px 0;">📍 Your Location</h3>
                <p style="margin: 0;">
                    <strong>City:</strong> <?php echo htmlspecialchars($userCity); ?><br>
                    <strong>Province:</strong> <?php echo htmlspecialchars($userProvince); ?><br>
                    <strong>Blood Group:</strong> <?php echo htmlspecialchars($userBloodGroup); ?>
                </p>
            </div>



            <div class="blood-availability">
                <h2 style="margin-top: 0;">Available Blood in Your Area (<?php echo htmlspecialchars($userCity); ?>)</h2>
                <?php if (!empty($bloodAvailability)): ?>
                    <?php foreach ($bloodAvailability as $bloodType => $info): ?>
                        <div class="blood-card">
                            <div>
                                <div class="blood-type"><?php echo htmlspecialchars($bloodType); ?></div>
                                <div class="blood-location">
                                    📍 Your City: <?php echo htmlspecialchars(implode(', ', array_unique($info['location']))); ?>
                                </div>
                            </div>
                            <div class="blood-quantity">
                                <?php echo htmlspecialchars($info['quantity']); ?> Units
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #666; text-align: center; padding: 20px;">
                        No blood currently available in your city.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($nearbyBloodAvailability)): ?>
                <div class="blood-availability">
                    <h2 style="margin-top: 0; color: #007bff;">Available Blood in Nearby Locations (<?php echo htmlspecialchars($userProvince); ?>)</h2>
                    <?php foreach ($nearbyBloodAvailability as $bloodType => $info): ?>
                        <div class="blood-card" style="border-left-color: #007bff;">
                            <div>
                                <div class="blood-type" style="color: #007bff;"><?php echo htmlspecialchars($bloodType); ?></div>
                                <div class="blood-location">
                                    📍 Nearby: <?php echo htmlspecialchars(implode(', ', array_unique($info['location']))); ?>
                                </div>
                            </div>
                            <div class="blood-quantity" style="background: #007bff;">
                                <?php echo htmlspecialchars($info['quantity']); ?> Units
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="form-section" id="sosForm">
                <h2>Emergency Request Details</h2>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    
                    <div class="info-grid">
                        <div class="form-group">
                            <label>Blood Type Needed:</label>
                            <select name="blood_needed" required>
                                <option value="<?php echo htmlspecialchars($userBloodGroup); ?>" selected>
                                    <?php echo htmlspecialchars($userBloodGroup); ?> (Your Blood Group)
                                </option>
                                <option value="">--- Other Blood Types ---</option>
                                <?php
                                $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($groups as $group):
                                    if ($group !== $userBloodGroup):
                                ?>
                                    <option value="<?php echo htmlspecialchars($group); ?>">
                                        <?php echo htmlspecialchars($group); ?>
                                    </option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Units Needed:</label>
                            <input type="number" name="units_needed" min="1" max="20" value="2" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Reason for Emergency:</label>
                        <textarea name="emergency_reason" placeholder="Describe the medical emergency..." required></textarea>
                    </div>

                    <button type="submit" name="sos_request" class="submit-btn">
                        <i class="ph-thin ph-warning" style="margin-right: 8px;"></i> Submit Emergency Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/dashboard-scripts.php"); ?>

<!-- SOS Terms and Conditions Modal -->
<div id="sosTermsModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:3000;justify-content:center;align-items:center;flex-direction:column;">
    <div style="background:white;border-radius:12px;padding:40px;width:90%;max-width:600px;box-shadow:0 10px 40px rgba(0,0,0,0.3);max-height:85vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;">
            <h2 style="margin:0;font-size:24px;font-weight:700;color:#8b0000;">⚠️ SOS Emergency Alert</h2>
            <button onclick="closeSosTermsModal()" style="background:none;border:none;font-size:28px;cursor:pointer;color:#999;">&times;</button>
        </div>

        <div style="background:#fff3cd;border:2px solid #ffc107;border-radius:8px;padding:20px;margin-bottom:25px;text-align:center;">
            <p style="margin:0;font-size:16px;font-weight:700;color:#856404;">READ CAREFULLY BEFORE PROCEEDING</p>
        </div>

        <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:8px;padding:20px;margin-bottom:25px;line-height:1.8;color:#333;font-size:14px;max-height:300px;overflow-y:auto;">
            <p style="margin:0 0 15px 0;font-weight:700;color:#8b0000;">TERMS AND CONDITIONS FOR SOS EMERGENCY ALERT</p>
            
            <p style="margin:0 0 12px 0;">The SOS button is <strong>strictly intended for genuine medical emergencies only</strong>, such as life-threatening situations requiring immediate blood assistance.</p>
            
            <p style="margin:0 0 12px 0;"><strong>By pressing the SOS button, you confirm that:</strong></p>
            <ul style="margin:0 0 12px 0;padding-left:20px;">
                <li>The request is <strong>urgent and made in good faith</strong></li>
                <li>A <strong>genuine medical emergency</strong> exists</li>
                <li>The information provided is <strong>accurate and truthful</strong></li>
            </ul>

            <p style="margin:0 0 12px 0;"><strong>Prohibited Activities:</strong></p>
            <ul style="margin:0 0 12px 0;padding-left:20px;">
                <li><strong>Misuse</strong> of the SOS feature for non-emergency purposes</li>
                <li><strong>Testing</strong> or prank calls</li>
                <li><strong>False or fraudulent</strong> emergency requests</li>
                <li><strong>Abusing</strong> the system to disrupt services</li>
            </ul>

            <p style="margin:0 0 12px 0;"><strong>Consequences of Misuse:</strong></p>
            <ul style="margin:0 0 15px 0;padding-left:20px;">
                <li>⚠️ Account suspension</li>
                <li>⚠️ Restriction of system access</li>
                <li>⚠️ Potential legal action in accordance with applicable laws and regulations</li>
            </ul>

            <p style="margin:0;font-style:italic;color:#666;">The system administrators reserve the right to verify emergency requests and take appropriate action against any abuse of this feature to ensure the safety and reliability of the service.</p>
        </div>

        <div style="background:#e8f5e9;border:2px solid #4caf50;border-radius:8px;padding:15px;margin-bottom:25px;">
            <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;margin:0;font-weight:600;color:#2e7d32;">
                <input type="checkbox" id="sosAgreementCheckbox" style="width:20px;height:20px;margin-top:2px;cursor:pointer;">
                <span>✓ I understand and agree to all terms and conditions. I confirm this is a genuine medical emergency and the information is accurate and truthful.</span>
            </label>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <button onclick="closeSosTermsModal()" style="background:#6c757d;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:700;font-size:14px;">Cancel</button>
            <button id="sosAgreeBtn" onclick="submitSosEmergency()" disabled style="background:#cccccc;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:not-allowed;font-weight:700;font-size:14px;transition:all 0.3s;">
                ✓ Agree and Continue
            </button>
        </div>
    </div>
</div>

<script>
function showSosTermsModal(event) {
    event.preventDefault();
    document.getElementById('sosTermsModal').style.display = 'flex';
    document.getElementById('sosAgreementCheckbox').checked = false;
    updateSosAgreeButton();
}

function closeSosTermsModal() {
    document.getElementById('sosTermsModal').style.display = 'none';
    document.getElementById('sosAgreementCheckbox').checked = false;
    updateSosAgreeButton();
}

function updateSosAgreeButton() {
    const checkbox = document.getElementById('sosAgreementCheckbox');
    const agreeBtn = document.getElementById('sosAgreeBtn');
    
    if (checkbox.checked) {
        agreeBtn.disabled = false;
        agreeBtn.style.background = '#8b0000';
        agreeBtn.style.cursor = 'pointer';
    } else {
        agreeBtn.disabled = true;
        agreeBtn.style.background = '#cccccc';
        agreeBtn.style.cursor = 'not-allowed';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('sosAgreementCheckbox');
    if (checkbox) {
        checkbox.addEventListener('change', updateSosAgreeButton);
    }
});

function submitSosEmergency() {
    if (!document.getElementById('sosAgreementCheckbox').checked) {
        alert('Please agree to the terms and conditions.');
        return;
    }

    // Close the modal
    closeSosTermsModal();
    
    // Submit the SOS form
    const sosForm = document.getElementById('sosForm');
    if (sosForm) {
        sosForm.submit();
    } else {
        alert('Error: Could not submit SOS request. Please try again.');
    }
}

// Close modal when clicking outside of it
document.addEventListener('click', function(event) {
    const modal = document.getElementById('sosTermsModal');
    if (modal && event.target === modal) {
        closeSosTermsModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeSosTermsModal();
    }
});
</script>

</body>
</html>
