<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

$wantsJson = isset($_GET['api'])
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

function getTodayAttendance($conn, $userId, $today) {
    $sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function buildAttendanceState($attendance) {
    $checkInIso = ($attendance && $attendance['check_in']) ? $attendance['check_in'] : null;
    $checkOutIso = ($attendance && $attendance['check_out']) ? $attendance['check_out'] : null;

    $checkInTs = $checkInIso ? strtotime($checkInIso) : null;
    $checkOutTs = $checkOutIso ? strtotime($checkOutIso) : null;

    $nowTs = time();
    $durationSeconds = 0;
    if ($checkInTs && $checkOutTs) {
        $durationSeconds = max(0, $checkOutTs - $checkInTs);
    } elseif ($checkInTs) {
        $durationSeconds = max(0, $nowTs - $checkInTs);
    }
    [$hours, $minutes] = formatDurationSeconds($durationSeconds);

    $nextAction = null;
    if (!$checkInIso) {
        $nextAction = 'checkin';
    } elseif (!$checkOutIso) {
        $nextAction = 'checkout';
    }

    $targetSeconds = 8 * 3600;
    $progressPercent = $targetSeconds > 0 ? min(100, ($durationSeconds / $targetSeconds) * 100) : 0;

    return [
        'checkIn' => $checkInIso,
        'checkOut' => $checkOutIso,
        'durationSeconds' => $durationSeconds,
        'durationText' => $hours . ' hr ' . $minutes . ' min',
        'progressPercent' => $progressPercent,
        'nextAction' => $nextAction,
    ];
}

$message = '';

$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if ($action !== 'checkin' && $action !== 'checkout') {
        if ($wantsJson) {
            jsonResponse(['ok' => false, 'message' => 'Aksi tidak valid.'], 400);
        }
        $message = 'Aksi tidak valid.';
    } elseif (!isLocationAllowed($lat, $lng, $user['location_lat'], $user['location_lng'])) {
        if ($wantsJson) {
            jsonResponse(['ok' => false, 'message' => 'Absen hanya bisa dilakukan di area yang ditentukan.'], 403);
        }
        $message = 'Absen hanya bisa dilakukan di area yang ditentukan.';
    } else {
        $now = date('Y-m-d H:i:s');
        $attendance = getTodayAttendance($conn, $user['id'], $today);

        if ($action === 'checkin') {
            if ($attendance && $attendance['check_in']) {
                $message = 'Anda sudah check in hari ini.';
                if ($wantsJson) {
                    jsonResponse(['ok' => false, 'message' => $message, 'state' => buildAttendanceState($attendance)], 409);
                }
            } else {
                $sql = "INSERT INTO attendance (employee_id, check_in, date, location_lat, location_lng) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdd", $user['id'], $now, $today, $lat, $lng);
                if (!$stmt->execute()) {
                    if ($wantsJson) {
                        jsonResponse(['ok' => false, 'message' => 'Gagal menyimpan check in.'], 500);
                    }
                    $message = 'Gagal menyimpan check in.';
                } else {
                    $message = 'Berhasil check in pada ' . date('H:i:s', strtotime($now));
                    $attendance = getTodayAttendance($conn, $user['id'], $today);
                    if ($wantsJson) {
                        jsonResponse(['ok' => true, 'message' => $message, 'state' => buildAttendanceState($attendance)]);
                    }
                }
            }
        }

        if ($action === 'checkout') {
            if (!$attendance || !$attendance['check_in']) {
                $message = 'Anda belum check in hari ini.';
                if ($wantsJson) {
                    jsonResponse(['ok' => false, 'message' => $message, 'state' => buildAttendanceState($attendance)], 409);
                }
            } elseif ($attendance['check_out']) {
                $message = 'Anda sudah check out hari ini.';
                if ($wantsJson) {
                    jsonResponse(['ok' => false, 'message' => $message, 'state' => buildAttendanceState($attendance)], 409);
                }
            } else {
                $sql = "UPDATE attendance SET check_out = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $now, $attendance['id']);
                if (!$stmt->execute()) {
                    if ($wantsJson) {
                        jsonResponse(['ok' => false, 'message' => 'Gagal menyimpan check out.'], 500);
                    }
                    $message = 'Gagal menyimpan check out.';
                } else {
                    $message = 'Berhasil check out pada ' . date('H:i:s', strtotime($now));
                    $attendance = getTodayAttendance($conn, $user['id'], $today);
                    if ($wantsJson) {
                        jsonResponse(['ok' => true, 'message' => $message, 'state' => buildAttendanceState($attendance)]);
                    }
                }
            }
        }
    }
}

$attendance = getTodayAttendance($conn, $user['id'], $today);
$state = buildAttendanceState($attendance);

if ($wantsJson && $_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResponse(['ok' => true, 'state' => $state]);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <header class="dashboard-header">
        <div class="user-chip">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="user-meta">
                <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
        </div>
        <a class="notif-btn" href="reports.php" aria-label="Notifikasi / History">
            History
        </a>
    </header>

    <main class="dashboard-main">
        <div class="clock-wrap">
            <div class="clock-logo" aria-hidden="true">Absen</div>
            <div class="clock-time" id="clock-time">--:--:--</div>
            <div class="clock-date" id="clock-date"><?php echo date('l, F j, Y'); ?></div>
        </div>

        <div class="finger-wrap">
            <button id="attendance-btn" class="finger-btn" type="button">
                <span class="finger-icon" aria-hidden="true">⟲</span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
            <div class="finger-label">Check In | Check Out</div>
        </div>

        <section class="work-card">
            <div class="work-meta">
                <div class="work-start" id="work-start">--:--:--</div>
                <div class="work-duration" id="work-duration">0 hr 0 min</div>
            </div>
            <div class="work-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                <div class="work-progress-bar" id="work-progress-bar"></div>
            </div>
        </section>

        <div class="quick-actions">
            <button class="quick-btn" id="break-btn" type="button">Mulai Istirahat</button>
            <button class="quick-btn" id="overtime-btn" type="button">Mulai Lembur</button>
        </div>

        <div class="nav-actions">
            <a class="nav-btn" href="dashboard.php">Visit Attendance</a>
            <a class="nav-btn" href="reports.php">Visit History</a>
        </div>

        <div class="dash-hint">
            Pastikan lokasi aktif dan berada di area yang ditentukan.
        </div>
    </main>

    <div class="dashboard-alerts" id="dashboard-alerts">
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Info:</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>

    <nav class="bottom-nav" aria-label="Bottom Navigation">
        <a class="bottom-item is-active" href="dashboard.php">Home</a>
        <a class="bottom-item" href="reports.php">History</a>
        <a class="bottom-item" href="logout.php">Logout</a>
    </nav>

    <script>
      window.__ATTENDANCE_STATE__ = <?php echo json_encode($state); ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
