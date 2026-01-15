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
        <h2>Selamat datang, <?php echo htmlspecialchars($user['name']); ?></h2>

        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
            <script>alert('<?php echo addslashes($message); ?>');</script>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Status Absen Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Check In:</strong> <?php echo $attendance ? date('H:i:s', strtotime($attendance['check_in'])) : 'Belum'; ?></p>
                        <p><strong>Check Out:</strong> <?php echo $attendance && $attendance['check_out'] ? date('H:i:s', strtotime($attendance['check_out'])) : 'Belum'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Aksi Absen</h5>
                    </div>
                    <div class="card-body">
                        <button id="checkin-btn" class="btn btn-success me-2" <?php echo $attendance && $attendance['check_in'] ? 'disabled' : ''; ?>>Check In</button>
                        <button id="checkout-btn" class="btn btn-danger" <?php echo !$attendance || !$attendance['check_in'] || $attendance['check_out'] ? 'disabled' : ''; ?>>Check Out</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
