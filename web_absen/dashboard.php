<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Check if location is allowed
    if (!isLocationAllowed($lat, $lng, $user['location_lat'], $user['location_lng'])) {
        $message = 'Absen hanya bisa dilakukan di area yang ditentukan.';
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
            }
        } else {
            $attendance = $result->fetch_assoc();
            if ($action == 'checkout' && $attendance['check_out'] == null) {
                $sql = "UPDATE attendance SET check_out = ?, location_lat = ?, location_lng = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $now, $lat, $lng, $attendance['id']);
                $stmt->execute();
                $message = 'Berhasil check out pada ' . date('H:i:s', strtotime($now));
            } elseif ($action == 'checkin') {
                $message = 'Anda sudah check in hari ini.';
            } else {
                $message = 'Anda sudah check out hari ini.';
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
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Absen</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="reports.php">Laporan</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-white">Selamat datang, <?php echo htmlspecialchars($user['name']); ?></h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Informasi:</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Status Absen Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="status-info mb-3">
                            <p class="mb-2"><strong>Tanggal:</strong> <?php echo date('d/m/Y'); ?></p>
                            <p class="mb-2"><strong>Check In:</strong>
                                <?php
                                if ($attendance && $attendance['check_in']) {
                                    echo '<span class="badge bg-success">' . date('H:i:s', strtotime($attendance['check_in'])) . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Belum Check In</span>';
                                }
                                ?>
                            </p>
                            <p class="mb-0"><strong>Check Out:</strong>
                                <?php
                                if ($attendance && $attendance['check_out']) {
                                    echo '<span class="badge bg-danger">' . date('H:i:s', strtotime($attendance['check_out'])) . '</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">Belum Check Out</span>';
                                }
                                ?>
                            </p>
                        </div>
                        <?php if ($attendance && $attendance['check_in'] && $attendance['check_out']):
                            $checkin_time = strtotime($attendance['check_in']);
                            $checkout_time = strtotime($attendance['check_out']);
                            $duration = $checkout_time - $checkin_time;
                            $hours = floor($duration / 3600);
                            $minutes = floor(($duration % 3600) / 60);
                        ?>
                        <div class="alert alert-success mb-0">
                            <strong>Durasi Kerja:</strong> <?php echo $hours; ?> jam <?php echo $minutes; ?> menit
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Aksi Absen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button id="checkin-btn" class="btn btn-success btn-lg" <?php echo $attendance && $attendance['check_in'] ? 'disabled' : ''; ?>>
                                <span class="btn-text">Check In</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                            <button id="checkout-btn" class="btn btn-danger btn-lg" <?php echo !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : ''; ?>>
                                <span class="btn-text">Check Out</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                Pastikan lokasi Anda aktif dan Anda berada di area yang ditentukan.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
