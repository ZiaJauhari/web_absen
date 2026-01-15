<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;

    // Validate coordinates
    if ($lat === null || $lng === null) {
        $message = 'Koordinat lokasi tidak valid.';
        $messageType = 'danger';
    } else {
        // Check if location is allowed
        if (!isLocationAllowed($lat, $lng, $user['location_lat'], $user['location_lng'])) {
            $message = 'Absen hanya bisa dilakukan di area yang ditentukan. Jarak Anda terlalu jauh dari lokasi kantor.';
            $messageType = 'danger';
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
                    $stmt->bind_param("issdd", $user['id'], $now, $today, $lat, $lng);
                    
                    if ($stmt->execute()) {
                        $message = 'Berhasil check in pada ' . date('H:i:s', strtotime($now));
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal melakukan check in. Silakan coba lagi.';
                        $messageType = 'danger';
                    }
                } else {
                    $message = 'Anda belum check in hari ini.';
                    $messageType = 'warning';
                }
            } else {
                $attendance = $result->fetch_assoc();
                if ($action == 'checkout' && $attendance['check_out'] == null) {
                    $sql = "UPDATE attendance SET check_out = ?, location_lat = ?, location_lng = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sddi", $now, $lat, $lng, $attendance['id']);
                    
                    if ($stmt->execute()) {
                        $message = 'Berhasil check out pada ' . date('H:i:s', strtotime($now));
                        $messageType = 'success';
                    } else {
                        $message = 'Gagal melakukan check out. Silakan coba lagi.';
                        $messageType = 'danger';
                    }
                } elseif ($action == 'checkin') {
                    $message = 'Anda sudah check in hari ini.';
                    $messageType = 'warning';
                } else {
                    $message = 'Anda sudah check out hari ini.';
                    $messageType = 'warning';
                }
            }
        }
    }
}

// Get today's attendance
$today = date('Y-m-d');
$sql = "SELECT * FROM attendance WHERE employee_id = ? AND date = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user['id'], $today);
$stmt->execute();
$attendance = $stmt->get_result()->fetch_assoc();

// Calculate work duration if both check in and check out exist
$workDuration = '';
if ($attendance && $attendance['check_in'] && $attendance['check_out']) {
    $checkIn = new DateTime($attendance['check_in']);
    $checkOut = new DateTime($attendance['check_out']);
    $interval = $checkIn->diff($checkOut);
    $workDuration = $interval->format('%h jam %i menit');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-clock"></i> Sistem Absen
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> Laporan
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>
            <i class="fas fa-user-circle"></i> Selamat datang, <?php echo htmlspecialchars($user['name']); ?>
        </h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-check"></i> Status Absen Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="status-card">
                            <p class="mb-2">
                                <strong><i class="fas fa-sign-in-alt text-success"></i> Check In:</strong> 
                                <?php echo $attendance && $attendance['check_in'] ? date('H:i:s', strtotime($attendance['check_in'])) : '<span class="text-muted">Belum check in</span>'; ?>
                            </p>
                        </div>
                        <div class="status-card">
                            <p class="mb-2">
                                <strong><i class="fas fa-sign-out-alt text-danger"></i> Check Out:</strong> 
                                <?php echo $attendance && $attendance['check_out'] ? date('H:i:s', strtotime($attendance['check_out'])) : '<span class="text-muted">Belum check out</span>'; ?>
                            </p>
                        </div>
                        <?php if ($workDuration): ?>
                        <div class="status-card">
                            <p class="mb-0">
                                <strong><i class="fas fa-hourglass-half text-primary"></i> Durasi Kerja:</strong> 
                                <?php echo $workDuration; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <p class="text-muted mb-0">
                                <i class="fas fa-calendar"></i> <?php echo formatDateIndo($today); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-clock"></i> <span id="current-time"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-hand-pointer"></i> Aksi Absen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <button id="checkin-btn" class="btn btn-success btn-lg" <?php echo $attendance && $attendance['check_in'] ? 'disabled' : ''; ?>>
                                <i class="fas fa-sign-in-alt"></i> Check In
                            </button>
                            <button id="checkout-btn" class="btn btn-danger btn-lg" <?php echo !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : ''; ?>>
                                <i class="fas fa-sign-out-alt"></i> Check Out
                            </button>
                        </div>
                        <div class="mt-4 p-3" style="background: #f0f9ff; border-radius: 10px; border-left: 4px solid #0ea5e9;">
                            <p class="mb-2"><strong><i class="fas fa-info-circle text-info"></i> Informasi:</strong></p>
                            <ul class="mb-0" style="font-size: 0.9rem;">
                                <li>Pastikan GPS/lokasi Anda aktif</li>
                                <li>Absen hanya bisa dilakukan di area kantor</li>
                                <li>Check in di awal hari kerja</li>
                                <li>Check out di akhir hari kerja</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Riwayat Absen Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get last 5 attendance records
                        $sql = "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 5";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user['id']);
                        $stmt->execute();
                        $recentAttendances = $stmt->get_result();
                        ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                                        <th><i class="fas fa-sign-in-alt"></i> Check In</th>
                                        <th><i class="fas fa-sign-out-alt"></i> Check Out</th>
                                        <th><i class="fas fa-hourglass-half"></i> Durasi</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentAttendances->num_rows > 0): ?>
                                        <?php while ($att = $recentAttendances->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo formatDateIndo($att['date']); ?></td>
                                                <td><?php echo $att['check_in'] ? date('H:i:s', strtotime($att['check_in'])) : '-'; ?></td>
                                                <td><?php echo $att['check_out'] ? date('H:i:s', strtotime($att['check_out'])) : '-'; ?></td>
                                                <td>
                                                    <?php
                                                    if ($att['check_in'] && $att['check_out']) {
                                                        $checkIn = new DateTime($att['check_in']);
                                                        $checkOut = new DateTime($att['check_out']);
                                                        $interval = $checkIn->diff($checkOut);
                                                        echo $interval->format('%h jam %i menit');
                                                    } else {
                                                        echo '-';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($att['check_in'] && $att['check_out']) {
                                                        echo '<span class="badge bg-success"><i class="fas fa-check"></i> Lengkap</span>';
                                                    } elseif ($att['check_in']) {
                                                        echo '<span class="badge bg-warning"><i class="fas fa-exclamation"></i> Check In Saja</span>';
                                                    } else {
                                                        echo '<span class="badge bg-danger"><i class="fas fa-times"></i> Tidak Hadir</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">📋</div>
                                                    <div class="empty-state-text">Belum ada riwayat absen</div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="reports.php" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> Lihat Semua Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID');
            document.getElementById('current-time').textContent = timeString;
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
    <script src="script.js"></script>
</body>
</html>
