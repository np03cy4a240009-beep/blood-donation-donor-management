<?php
include("../config/user-session.php");
include("../config/db.php");
include("../includes/functions.php");
include("../includes/eligibility.php");
require_once("../includes/security.php"); // for csrfField() and csrfCheck()

$user_id = $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$evaluation = evaluateDonorEligibility($userData ?: []);
$eligible = ($evaluation['status'] === 'eligible');

$userBloodGroup = $userData['blood_group'] ?? '';
$isRare = isRareBloodGroup($userBloodGroup);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['appointment_date']) && $eligible) {
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $status = 'pending';

        if ($appointment_date === '' || $appointment_time === '' || $location === '') {
            $message = "Please fill all fields.";
        } elseif ($appointment_date < date('Y-m-d')) {
            $message = "Appointment date cannot be in the past.";
        } else {
            $check = $conn->prepare("
                SELECT id FROM appointments 
                WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'
                LIMIT 1
            ");
            $check->bind_param("ss", $appointment_date, $appointment_time);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows > 0) {
                $message = "This time slot is already booked.";
            } else {
                $stmt = $conn->prepare("INSERT INTO appointments (user_id, appointment_date, appointment_time, location, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $user_id, $appointment_date, $appointment_time, $location, $status);

                if ($stmt->execute()) {
                    header("Location: appointments.php?booked=1");
                    exit();
                } else {
                    $message = "Failed to book appointment.";
                }
            }
        }
    } elseif (isset($_POST['cancel'])) {
        // CSRF check
        if (!csrfCheck($_POST['csrf_token'] ?? '')) {
            $message = "Invalid CSRF token.";
        } else {
            $cancel_id = (int)$_POST['cancel'];
            // Ensure user owns this appointment
            $del = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            $del->bind_param("ii", $cancel_id, $user_id);
            if ($del->execute()) {
                header("Location: appointments.php?cancelled=1");
                exit();
            } else {
                $message = "Failed to cancel appointment.";
            }
        }
    }
}

$appointments = $conn->prepare("SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time ASC");
$appointments->bind_param("i", $user_id);
$appointments->execute();
$result = $appointments->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/user-appointment-organized.css">
</head>
<body>
<div class="dashboard-layout">
    <?php include("../includes/sidebar-user.php"); ?>

    <div class="main-panel">
        <div class="topbar"><div class="menu-btn">≡</div></div>
        <h1 class="page-title">Appointment</h1>

        <?php if (isset($_GET['booked'])): ?>
            <div class="notice-box notice-success">Appointment booked successfully.</div>
        <?php elseif (isset($_GET['cancelled'])): ?>
            <div class="notice-box notice-success">Appointment cancelled successfully.</div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="notice-box notice-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Eligibility Modal -->
        <?php if (!$eligible): ?>
        <div id="eligibilityModal" class="modal-overlay">
            <div class="modal-content eligibility-modal">
                <div class="modal-header">
                    <span class="modal-icon">❌</span>
                </div>
                <div class="modal-body">
                    <h2>You Are not<br>Eligible to Donate !</h2>
                    <p><?php echo htmlspecialchars($evaluation['reason']); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-close-modal">Cancel</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="two-col">
            <div class="slot-panel">
                <h2 style="color:#fff;">Book Appointment</h2>

                <div class="card" style="padding:18px;margin:20px 0;">
                    <strong style="color:#222;">
                        <span class="eligibility-badge <?php echo $eligible ? 'eligible' : 'ineligible'; ?>">
                            <?php echo $eligible ? '✓ Eligible to donate' : '❌ Not eligible to donate'; ?>
                        </span>
                    </strong>
                    <br><br>
                    <strong style="color:#222;">Eligibility Note:</strong>
                    <div style="margin-top:8px;color:#222;">
                        <?php echo htmlspecialchars($evaluation['reason']); ?>
                    </div>
                </div>

                <?php if ($isRare): ?>
                    <div class="notice-box notice-info">
                        Rare blood group exception: donor is priority contact for urgent coordination, but Nepal donor safety rules still apply.
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php echo csrfField(); ?>
                    <label>Calendar</label>
                    <input type="date" name="appointment_date" min="<?php echo date('Y-m-d'); ?>" class="card" style="height:50px;margin:12px 0;width:100%;padding:12px;border:none;" <?php echo !$eligible ? 'disabled' : ''; ?>>

                    <label>Available Time Slots:</label>
                    <select name="appointment_time" class="card" style="height:50px;margin:12px 0;width:100%;padding:12px;border:none;" <?php echo !$eligible ? 'disabled' : ''; ?>>
                        <option value="9 AM">9 AM</option>
                        <option value="10 AM">10 AM</option>
                        <option value="11 AM">11 AM</option>
                        <option value="1 PM">1 PM</option>
                        <option value="2 PM">2 PM</option>
                        <option value="3 PM">3 PM</option>
                    </select>

                    <label>Location:</label>
                    <input type="text" name="location" class="card" style="height:50px;margin:12px 0;width:100%;padding:12px;border:none;" value="Bir Hospital" <?php echo !$eligible ? 'disabled' : ''; ?>>

                    <button type="submit" class="btn" style="background:#b13232;color:#fff;width:100%;text-align:center;" <?php echo !$eligible ? 'disabled' : ''; ?>>
                        Book Appointment
                    </button>
                </form>
            </div>

            <div class="slot-panel">
                <h2 style="color:#fff;">Upcoming Appointments</h2>

                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="card" style="padding:22px;margin-top:18px;color:#222;">
                        <h3>Appointment</h3>
                        <p style="margin-top:16px;">Date: <?php echo htmlspecialchars($row['appointment_date']); ?></p>
                        <p>Time: <?php echo htmlspecialchars($row['appointment_time']); ?></p>
                        <p>Location: <?php echo htmlspecialchars($row['location']); ?></p>
                        <p>Status:
                            <span class="<?php echo getStatusBadge($row['status']); ?>">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </p>

                        <?php if ($row['status'] !== 'cancelled'): ?>
                            <form method="POST" action="appointments.php" style="margin-top:16px;">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="cancel" value="<?php echo (int)$row['id']; ?>">
                                <button type="submit" class="btn" style="display:block;background:#a23333;color:#fff;text-align:center;margin-top:16px;width:100%;">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card" style="padding:18px;margin-top:18px;color:#222;">No appointments yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/dashboard.js"></script>
<script>
    // Eligibility Modal Handler
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('eligibilityModal');
        const closeBtn = document.querySelector('.btn-close-modal');
        
        if (closeBtn && modal) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            });
        }

        // Close modal when clicking outside
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => {
                        modal.style.display = 'none';
                    }, 300);
                }
            });
        }
    });
</script>
</body>
</html>