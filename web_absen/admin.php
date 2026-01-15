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
    $nim = $_POST['nim'] ?: null;
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $lat = $_POST['lat'] ?: null;
    $lng = $_POST['lng'] ?: null;

    $sql = "INSERT INTO employees (name, nim, email, password, role, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssdd", $name, $nim, $email, $password, $role, $lat, $lng);
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
$sql = "SELECT a.*, e.name, e.nim FROM attendance a JOIN employees e ON a.employee_id = e.id ORDER BY a.date DESC, a.check_in DESC";
$attendances = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sistem Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="#">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-shield-check me-2" viewBox="0 0 16 16">
                    <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56"/>
                    <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0"/>
                </svg>
                Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
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
                <h3 class="fw-bold mb-1">Admin Panel</h3>
                <p class="text-muted mb-0">Kelola karyawan dan data absensi</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Informasi:</strong> <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0 fw-semibold">Tambah Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold">Nama Lengkap</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Masukkan nama lengkap">
                            </div>
                            <div class="mb-3">
                                <label for="nim" class="form-label fw-semibold">NIM</label>
                                <input type="text" class="form-control" id="nim" name="nim" placeholder="Masukkan NIM (opsional)">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="contoh@email.com">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6" placeholder="Min. 6 karakter">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label fw-semibold">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="employee">Karyawan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="lat" class="form-label fw-semibold">Latitude Lokasi</label>
                                <input type="text" class="form-control" id="lat" name="lat" placeholder="-6.200000">
                                <small class="text-muted">Opsional</small>
                            </div>
                            <div class="mb-3">
                                <label for="lng" class="form-label fw-semibold">Longitude Lokasi</label>
                                <input type="text" class="form-control" id="lng" name="lng" placeholder="106.816666">
                                <small class="text-muted">Opsional</small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="add_employee" class="btn btn-primary">
                                    Tambah Karyawan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">Daftar Karyawan</h5>
                        <span class="badge bg-primary"><?php echo $employees->num_rows; ?> Karyawan</span>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $employees->data_seek(0); // Reset pointer
                        if ($employees->num_rows > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="border-0">No</th>
                                        <th class="border-0">Nama</th>
                                        <th class="border-0">NIM</th>
                                        <th class="border-0">Email</th>
                                        <th class="border-0">Role</th>
                                        <th class="border-0">Lokasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    while ($emp = $employees->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                            <td><?php echo $emp['nim'] ? htmlspecialchars($emp['nim']) : '<span class="text-muted">-</span>'; ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $emp['role'] == 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                                    <?php echo ucfirst($emp['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($emp['location_lat'] && $emp['location_lng']): ?>
                                                    <span class="badge bg-success-subtle text-success" title="<?php echo $emp['location_lat'] . ', ' . $emp['location_lng']; ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-geo-alt-fill" viewBox="0 0 16 16">
                                                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/>
                                                        </svg>
                                                        Ada
                                                    </span>
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
                        <div class="text-center py-5">
                            <p class="text-muted">Belum ada karyawan terdaftar.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-semibold">Laporan Absensi Semua Karyawan</h5>
                <span class="badge bg-primary"><?php echo $attendances->num_rows; ?> Record</span>
            </div>
            <div class="card-body p-0">
                <?php
                $attendances->data_seek(0); // Reset pointer
                if ($attendances->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0">No</th>
                                <th class="border-0">Nama</th>
                                <th class="border-0">NIM</th>
                                <th class="border-0">Tanggal</th>
                                <th class="border-0">Jam Masuk</th>
                                <th class="border-0">Jam Keluar</th>
                                <th class="border-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while ($att = $attendances->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($att['name']); ?></td>
                                    <td><?php echo $att['nim'] ? htmlspecialchars($att['nim']) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($att['date'])); ?></td>
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
                                            echo '<span class="badge bg-success">Lengkap</span>';
                                        } elseif ($att['check_in']) {
                                            echo '<span class="badge bg-warning">Belum Checkout</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">Tidak Hadir</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
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
