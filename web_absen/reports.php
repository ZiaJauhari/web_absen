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
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="mb-0">Laporan Absen</h2>
            <div class="text-muted"><?php echo e($user['name']); ?></div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Riwayat Absen</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendances as $att): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($att['date'])); ?></td>
                                <td><?php echo $att['check_in'] ? date('H:i:s', strtotime($att['check_in'])) : '-'; ?></td>
                                <td><?php echo $att['check_out'] ? date('H:i:s', strtotime($att['check_out'])) : '-'; ?></td>
                                <td>
                                    <?php
                                    if ($att['check_in'] && $att['check_out']) {
                                        echo '<span class="badge bg-success">Lengkap</span>';
                                    } elseif ($att['check_in']) {
                                        echo '<span class="badge bg-warning">Check In Saja</span>';
                                    } else {
                                        echo '<span class="badge bg-danger">Tidak Hadir</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($attendances) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada data absen.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
