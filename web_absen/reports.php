<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

// Get attendance records
$sql = "SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$attendances = [];
while ($row = $result->fetch_assoc()) {
    $attendances[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absen - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Sistem Absen</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-white">Laporan Absen - <?php echo htmlspecialchars($user['name']); ?></h2>
            </div>
        </div>

        <?php
        // Calculate summary statistics
        $total_days = count($attendances);
        $complete_days = 0;
        $incomplete_days = 0;
        $total_hours = 0;

        foreach ($attendances as $att) {
            if ($att['check_in'] && $att['check_out']) {
                $complete_days++;
                $checkin_time = strtotime($att['check_in']);
                $checkout_time = strtotime($att['check_out']);
                $duration = $checkout_time - $checkin_time;
                $total_hours += $duration / 3600;
            } elseif ($att['check_in']) {
                $incomplete_days++;
            }
        }
        $avg_hours = $complete_days > 0 ? $total_hours / $complete_days : 0;
        ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary mb-0"><?php echo $total_days; ?></h3>
                        <p class="text-muted mb-0">Total Hari</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success mb-0"><?php echo $complete_days; ?></h3>
                        <p class="text-muted mb-0">Hari Lengkap</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-warning mb-0"><?php echo $incomplete_days; ?></h3>
                        <p class="text-muted mb-0">Hari Tidak Lengkap</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-info mb-0"><?php echo number_format($avg_hours, 1); ?>j</h3>
                        <p class="text-muted mb-0">Rata-rata Jam Kerja</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Riwayat Absen</h5>
                <span class="badge bg-light text-dark"><?php echo count($attendances); ?> record</span>
            </div>
            <div class="card-body">
                <?php if (count($attendances) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Durasi Kerja</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendances as $att): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($att['date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('l', strtotime($att['date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($att['check_in']): ?>
                                            <span class="badge bg-success"><?php echo date('H:i:s', strtotime($att['check_in'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($att['check_out']): ?>
                                            <span class="badge bg-danger"><?php echo date('H:i:s', strtotime($att['check_out'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($att['check_in'] && $att['check_out']) {
                                            $checkin_time = strtotime($att['check_in']);
                                            $checkout_time = strtotime($att['check_out']);
                                            $duration = $checkout_time - $checkin_time;
                                            $hours = floor($duration / 3600);
                                            $minutes = floor(($duration % 3600) / 60);

                                            $color = 'text-success';
                                            if ($hours < 8) {
                                                $color = 'text-warning';
                                            }

                                            echo '<strong class="' . $color . '">' . $hours . ' jam ' . $minutes . ' menit</strong>';
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($att['check_in'] && $att['check_out']) {
                                            echo '<span class="badge bg-success">Lengkap</span>';
                                        } elseif ($att['check_in']) {
                                            echo '<span class="badge bg-warning text-dark">Check In Saja</span>';
                                        } else {
                                            echo '<span class="badge bg-danger">Tidak Hadir</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">Belum ada riwayat absensi.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
