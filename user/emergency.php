<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");
require_once("../includes/security.php");

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';
$bloodAvailability = [];
$nearbyBloodAvailability = [];

try {
    // Get user's location
    $userStmt = $conn->prepare("SELECT address, city, province, blood_group FROM users WHERE id = ?");
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

// Handle SOS request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sos_request'])) {
    // Verify CSRF token
    if (!csrfCheck()) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    } else {
        try {
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
                    (request_id, user_id, contact, location, blood_type, units, urgency, status, request_date, required_by, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if (!$emergencyStmt) {
                    throw new Exception("Prepare error: " . $conn->error);
                }
                
                $emergencyStmt->bind_param(
                    "sisssisssss",
                    $request_id,
                    $user_id,
                    $userPhone,
                    $userLocation,
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

<script src="../assets/js/dashboard.js"></script>
</body>
</html>
