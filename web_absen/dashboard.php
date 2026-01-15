<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Check if location is allowed
    if (!isLocationAllowed($lat, $lng, $user['location_lat'], $user['location_lng'])) {
        $message = 'Absen hanya bisa dilakukan di area yang ditentukan.';
        $messageType = 'error';
    } else {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        // Check if already checked in today
        $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user['id'], $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // First check in
            if ($action == 'checkin') {
                $sql = "INSERT INTO attendance (employee_id, check_in, date, location_lat, location_lng) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issss", $user['id'], $now, $today, $lat, $lng);
                $stmt->execute();
                $message = 'Berhasil check in pada ' . date('H:i:s', strtotime($now));
                $messageType = 'success';
            }
        } else {
            $attendance = $result->fetch_assoc();
            if ($action == 'checkout' && $attendance['check_out'] == null) {
                $sql = "UPDATE attendance SET check_out = ?, location_lat = ?, location_lng = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $now, $lat, $lng, $attendance['id']);
                $stmt->execute();
                $message = 'Berhasil check out pada ' . date('H:i:s', strtotime($now));
                $messageType = 'success';
            } elseif ($action == 'checkin') {
                $message = 'Anda sudah check in hari ini.';
                $messageType = 'error';
            } else {
                $message = 'Anda sudah check out hari ini.';
                $messageType = 'error';
            }
        }
    }

    // Return JSON for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['message' => $message, 'type' => $messageType]);
        exit();
    }
}

// Get today's attendance
$today = date('Y-m-d');
$sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user['id'], $today);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

// Calculate working hours
$workingHours = 0;
$workingMinutes = 0;
if ($attendance && $attendance['check_in']) {
    $checkin_time = strtotime($attendance['check_in']);
    $current_time = $attendance['check_out'] ? strtotime($attendance['check_out']) : time();
    $duration = $current_time - $checkin_time;
    $workingHours = floor($duration / 3600);
    $workingMinutes = floor(($duration % 3600) / 60);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Kolabo App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="attendance-page">
    <div class="attendance-container">
        <!-- Header with User Profile -->
        <header class="attendance-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h2 class="user-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>
            <button class="notification-btn" onclick="window.location.href='menu.php'">
                <i class="fas fa-bars"></i>
            </button>
        </header>

        <!-- Company Logo -->
        <div class="company-logo">
            <div class="logo-icon">
                <i class="fas fa-building"></i>
            </div>
        </div>

        <!-- Real-time Clock -->
        <div class="clock-display">
            <div class="current-time" id="current-time">00:00:00</div>
            <div class="current-date" id="current-date">Loading...</div>
        </div>

        <!-- Fingerprint Check In/Out Button -->
        <div class="fingerprint-section">
            <button class="fingerprint-btn" id="attendance-btn" data-action="<?php echo ($attendance && $attendance['check_in'] && !$attendance['check_out']) ? 'checkout' : 'checkin'; ?>">
                <div class="fingerprint-icon">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <div class="fingerprint-pulse"></div>
            </button>
            <p class="attendance-label">Check In | Check Out</p>
        </div>

        <!-- Working Hours Display -->
        <div class="working-hours">
            <div class="hours-info">
                <span class="check-time">
                    <?php
                    if ($attendance && $attendance['check_in']) {
                        echo date('H:i:s', strtotime($attendance['check_in']));
                    } else {
                        echo '--:--:--';
                    }
                    ?>
                </span>
                <span class="separator">-</span>
                <span class="check-time checkout-time">
                    <?php
                    if ($attendance && $attendance['check_out']) {
                        echo date('H:i:s', strtotime($attendance['check_out']));
                    } else {
                        echo '--:--:--';
                    }
                    ?>
                </span>
            </div>
            <div class="hours-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo min(($workingHours * 60 + $workingMinutes) / 480 * 100, 100); ?>%"></div>
                </div>
                <span class="hours-text"><?php echo $workingHours; ?> hr <?php echo $workingMinutes; ?> min</span>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="quick-actions">
            <button class="action-btn">
                <i class="fas fa-pause"></i>
                <span>Mulai Istirahat</span>
                <small>- - -</small>
            </button>
            <button class="action-btn">
                <i class="fas fa-clock"></i>
                <span>Mulai Lembur</span>
                <small>- - -</small>
            </button>
        </div>

        <!-- Additional Actions -->
        <div class="additional-actions">
            <button class="text-btn" onclick="window.location.href='reports.php'">
                <i class="fas fa-calendar-check"></i>
                Visit Attendance
            </button>
            <button class="text-btn" onclick="window.location.href='reports.php'">
                Visit History
            </button>
        </div>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-home"></i>
            </a>
            <a href="menu.php" class="nav-item nav-center">
                <div class="center-icon">
                    <i class="fas fa-th-large"></i>
                </div>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-comment"></i>
            </a>
        </nav>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast-notification"></div>

    <script src="script.js"></script>
</body>
</html>
