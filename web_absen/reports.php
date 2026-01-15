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
    <title>Laporan Absensi - Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-calendar-check me-2" viewBox="0 0 16 16">
                    <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
                Sistem Absensi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">Laporan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Keluar</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">
        <div class="row mb-4">
            <div class="col">
                <h3 class="fw-bold mb-1">Laporan Absensi</h3>
                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($user['name']); ?>
                    <?php if ($user['nim']): ?>
                        | NIM: <?php echo htmlspecialchars($user['nim']); ?>
                    <?php endif; ?>
                </p>
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

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card info-card">
                    <div class="card-body text-center">
                        <div class="icon-box bg-primary-subtle text-primary mx-auto mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-calendar3" viewBox="0 0 16 16">
                                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2M1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857z"/>
                                <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2m3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                            </svg>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo $total_days; ?></h3>
                        <p class="text-muted mb-0 small">Total Hari</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card">
                    <div class="card-body text-center">
                        <div class="icon-box bg-success-subtle text-success mx-auto mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                            </svg>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo $complete_days; ?></h3>
                        <p class="text-muted mb-0 small">Hari Lengkap</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card">
                    <div class="card-body text-center">
                        <div class="icon-box bg-warning-subtle text-warning mx-auto mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-exclamation-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                            </svg>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo $incomplete_days; ?></h3>
                        <p class="text-muted mb-0 small">Hari Tidak Lengkap</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card info-card">
                    <div class="card-body text-center">
                        <div class="icon-box bg-info-subtle text-info mx-auto mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16m7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0"/>
                            </svg>
                        </div>
                        <h3 class="fw-bold mb-1"><?php echo number_format($avg_hours, 1); ?>j</h3>
                        <p class="text-muted mb-0 small">Rata-rata Jam Kerja</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">Riwayat Absensi</h5>
                <span class="badge bg-primary"><?php echo count($attendances); ?> Record</span>
            </div>
            <div class="card-body p-0">
                <?php if (count($attendances) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">No</th>
                                <th class="border-0">Tanggal</th>
                                <th class="border-0">Jam Masuk</th>
                                <th class="border-0">Jam Keluar</th>
                                <th class="border-0">Durasi Kerja</th>
                                <th class="border-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($attendances as $att): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($att['date'])); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo date('l', strtotime($att['date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($att['check_in']): ?>
                                            <span class="badge bg-success-subtle text-success"><?php echo date('H:i:s', strtotime($att['check_in'])); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($att['check_out']): ?>
                                            <span class="badge bg-danger-subtle text-danger"><?php echo date('H:i:s', strtotime($att['check_out'])); ?></span>
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

                                            echo '<strong class="' . $color . '">' . $hours . 'j ' . $minutes . 'm</strong>';
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
                                            echo '<span class="badge bg-warning">Belum Checkout</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Tidak Hadir</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <p class="text-muted">Belum ada riwayat absensi.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
