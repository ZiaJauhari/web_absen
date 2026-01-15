<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();

// Get filter parameters
$filterMonth = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$filterYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get attendance records with filter
$sql = "SELECT * FROM attendance 
        WHERE employee_id = ? 
        AND MONTH(date) = ? 
        AND YEAR(date) = ?
        ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user['id'], $filterMonth, $filterYear);
$stmt->execute();
$result = $stmt->get_result();
$attendances = [];
while ($row = $result->fetch_assoc()) {
    $attendances[] = $row;
}

// Calculate statistics
$totalDays = count($attendances);
$completeDays = 0;
$incompleteDays = 0;
$totalWorkHours = 0;

foreach ($attendances as $att) {
    if ($att['check_in'] && $att['check_out']) {
        $completeDays++;
        $checkIn = new DateTime($att['check_in']);
        $checkOut = new DateTime($att['check_out']);
        $interval = $checkIn->diff($checkOut);
        $totalWorkHours += $interval->h + ($interval->i / 60);
    } elseif ($att['check_in']) {
        $incompleteDays++;
    }
}

$averageWorkHours = $totalDays > 0 ? $totalWorkHours / $completeDays : 0;

// Get months and years for filter
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$currentYear = date('Y');
$years = range($currentYear - 2, $currentYear);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absen - Sistem Absen Karyawan</title>
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
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="reports.php">
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
            <i class="fas fa-chart-line"></i> Laporan Absen - <?php echo htmlspecialchars($user['name']); ?>
        </h2>

        <!-- Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-filter"></i> Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label for="month" class="form-label">
                            <i class="fas fa-calendar-alt"></i> Bulan
                        </label>
                        <select class="form-control" id="month" name="month">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $num == $filterMonth ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="year" class="form-label">
                            <i class="fas fa-calendar"></i> Tahun
                        </label>
                        <select class="form-control" id="year" name="year">
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $filterYear ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card" style="border-left: 4px solid #3b82f6;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Hari</h6>
                                <h3 class="mb-0"><?php echo $totalDays; ?></h3>
                            </div>
                            <div class="text-primary" style="font-size: 2rem;">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="border-left: 4px solid #10b981;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Absen Lengkap</h6>
                                <h3 class="mb-0"><?php echo $completeDays; ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2rem;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="border-left: 4px solid #f59e0b;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Tidak Lengkap</h6>
                                <h3 class="mb-0"><?php echo $incompleteDays; ?></h3>
                            </div>
                            <div class="text-warning" style="font-size: 2rem;">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card" style="border-left: 4px solid #8b5cf6;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Rata-rata Jam</h6>
                                <h3 class="mb-0"><?php echo number_format($averageWorkHours, 1); ?></h3>
                            </div>
                            <div style="color: #8b5cf6; font-size: 2rem;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history"></i> Riwayat Absen - <?php echo $months[$filterMonth] . ' ' . $filterYear; ?>
                </h5>
                <button onclick="window.print()" class="btn btn-sm btn-success">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> No</th>
                                <th><i class="fas fa-calendar"></i> Tanggal</th>
                                <th><i class="fas fa-calendar-week"></i> Hari</th>
                                <th><i class="fas fa-sign-in-alt"></i> Check In</th>
                                <th><i class="fas fa-sign-out-alt"></i> Check Out</th>
                                <th><i class="fas fa-hourglass-half"></i> Durasi</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attendances) > 0): ?>
                                <?php 
                                $no = 1;
                                $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                                foreach ($attendances as $att): 
                                    $dayName = $days[date('w', strtotime($att['date']))];
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo formatDateIndo($att['date']); ?></td>
                                        <td><?php echo $dayName; ?></td>
                                        <td>
                                            <?php if ($att['check_in']): ?>
                                                <span class="badge bg-success">
                                                    <?php echo date('H:i:s', strtotime($att['check_in'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($att['check_out']): ?>
                                                <span class="badge bg-danger">
                                                    <?php echo date('H:i:s', strtotime($att['check_out'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($att['check_in'] && $att['check_out']) {
                                                $checkIn = new DateTime($att['check_in']);
                                                $checkOut = new DateTime($att['check_out']);
                                                $interval = $checkIn->diff($checkOut);
                                                echo '<strong>' . $interval->format('%h jam %i menit') . '</strong>';
                                            } else {
                                                echo '<span class="text-muted">-</span>';
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
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📋</div>
                                            <div class="empty-state-text">
                                                Tidak ada data absen untuk periode ini
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($attendances) > 0): ?>
                <div class="mt-4 p-3" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 10px;">
                    <h6 class="mb-3"><i class="fas fa-chart-pie"></i> Ringkasan</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Total Hari Kerja:</strong> <?php echo $totalDays; ?> hari</p>
                            <p class="mb-2"><strong>Absen Lengkap:</strong> <?php echo $completeDays; ?> hari</p>
                            <p class="mb-2"><strong>Absen Tidak Lengkap:</strong> <?php echo $incompleteDays; ?> hari</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Total Jam Kerja:</strong> <?php echo number_format($totalWorkHours, 1); ?> jam</p>
                            <p class="mb-2"><strong>Rata-rata Jam Kerja:</strong> <?php echo number_format($averageWorkHours, 1); ?> jam/hari</p>
                            <p class="mb-2"><strong>Tingkat Kehadiran:</strong> 
                                <?php 
                                $attendanceRate = $totalDays > 0 ? ($completeDays / $totalDays) * 100 : 0;
                                echo number_format($attendanceRate, 1); 
                                ?>%
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    
    <style>
        @media print {
            .navbar, .btn, .card-header button {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</body>
</html>
