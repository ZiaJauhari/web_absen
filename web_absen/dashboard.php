<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$flash = getFlash();

function redirectDashboard(): void {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if ($action !== 'checkin' && $action !== 'checkout') {
        setFlash('Aksi tidak valid.', 'danger');
        redirectDashboard();
    }

    if (!is_numeric($lat) || !is_numeric($lng)) {
        setFlash('Gagal mendapatkan lokasi. Pastikan izin lokasi aktif.', 'danger');
        redirectDashboard();
    }

    $lat = (float) $lat;
    $lng = (float) $lng;

    // Check if location is allowed
    if (!isLocationAllowed($lat, $lng, $user['location_lat'], $user['location_lng'])) {
        setFlash('Absen hanya bisa dilakukan di area yang ditentukan.', 'danger');
        redirectDashboard();
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
                    setFlash('Berhasil check in pada ' . date('H:i:s', strtotime($now)) . '.', 'success');
                } else {
                    setFlash('Gagal check in. Coba lagi.', 'danger');
                }
                redirectDashboard();
            } else {
                setFlash('Anda belum check in hari ini.', 'warning');
                redirectDashboard();
            }
        } else {
            $attendance = $result->fetch_assoc();
            if ($action == 'checkout' && $attendance['check_out'] == null) {
                $sql = "UPDATE attendance SET check_out = ?, location_lat = ?, location_lng = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sddi", $now, $lat, $lng, $attendance['id']);
                if ($stmt->execute()) {
                    setFlash('Berhasil check out pada ' . date('H:i:s', strtotime($now)) . '.', 'success');
                } else {
                    setFlash('Gagal check out. Coba lagi.', 'danger');
                }
                redirectDashboard();
            } elseif ($action == 'checkin') {
                setFlash('Anda sudah check in hari ini.', 'info');
                redirectDashboard();
            } else {
                setFlash('Anda sudah check out hari ini.', 'info');
                redirectDashboard();
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
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="mb-0">Selamat datang, <?php echo e($user['name']); ?></h2>
            <div class="text-muted small">Hari ini: <?php echo date('d/m/Y'); ?></div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?> shadow-sm" role="alert">
                <?php echo e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Status Absen Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Check In:</strong> <?php echo $attendance && $attendance['check_in'] ? date('H:i:s', strtotime($attendance['check_in'])) : 'Belum'; ?></p>
                        <p class="mb-0"><strong>Check Out:</strong> <?php echo $attendance && $attendance['check_out'] ? date('H:i:s', strtotime($attendance['check_out'])) : 'Belum'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Aksi Absen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <button id="checkin-btn" class="btn btn-success" <?php echo $attendance && $attendance['check_in'] ? 'disabled' : ''; ?>>
                                Check In
                            </button>
                            <button id="checkout-btn" class="btn btn-danger" <?php echo !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : ''; ?>>
                                Check Out
                            </button>
                            <div id="geo-status" class="text-muted small align-self-center"></div>
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
