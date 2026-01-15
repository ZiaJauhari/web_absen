<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
if ($user['role'] != 'admin') {
    header('Location: dashboard.php');
    exit();
}

$message = '';

// Handle add employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $lat = $_POST['lat'] ?: null;
    $lng = $_POST['lng'] ?: null;

    $sql = "INSERT INTO employees (name, email, password, role, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdd", $name, $email, $password, $role, $lat, $lng);
    if ($stmt->execute()) {
        $message = 'Karyawan berhasil ditambahkan.';
    } else {
        $message = 'Gagal menambahkan karyawan.';
    }
}

// Get all employees
$sql = "SELECT * FROM employees ORDER BY name";
$employees = $conn->query($sql);

// Get all attendance
$sql = "SELECT a.*, e.name FROM attendance a JOIN employees e ON a.employee_id = e.id ORDER BY a.date DESC, a.check_in DESC";
$attendances = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-white">Admin Panel</h2>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Informasi:</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Tambah Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Masukkan nama lengkap">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="contoh@email.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Min. 6 karakter">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="employee">Karyawan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="lat" class="form-label">Latitude Lokasi (opsional)</label>
                                <input type="text" class="form-control" id="lat" name="lat" placeholder="-6.200000">
                                <small class="text-muted">Contoh: -6.200000</small>
                            </div>
                            <div class="mb-3">
                                <label for="lng" class="form-label">Longitude Lokasi (opsional)</label>
                                <input type="text" class="form-control" id="lng" name="lng" placeholder="106.816666">
                                <small class="text-muted">Contoh: 106.816666</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_employee" class="btn btn-primary btn-lg">
                                    Tambah Karyawan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Daftar Karyawan (<?php echo $employees->num_rows; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $employees->data_seek(0); // Reset pointer
                        if ($employees->num_rows > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($emp = $employees->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $emp['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                                    <?php echo ucfirst($emp['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($emp['location_lat'] && $emp['location_lng']): ?>
                                                    <span class="badge bg-success" title="<?php echo $emp['location_lat'] . ', ' . $emp['location_lng']; ?>">Ada</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Tidak Ada</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <p class="text-muted">Belum ada karyawan terdaftar.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Laporan Absen Semua Karyawan</h5>
                <span class="badge bg-light text-dark"><?php echo $attendances->num_rows; ?> record</span>
            </div>
            <div class="card-body">
                <?php
                $attendances->data_seek(0); // Reset pointer
                if ($attendances->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Durasi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($att = $attendances->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($att['name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($att['date'])); ?></td>
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
                                            echo $hours . 'j ' . $minutes . 'm';
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">Belum ada data absensi.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
