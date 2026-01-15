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

$flash = getFlash();

// Handle add employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $hasLat = isset($_POST['lat']) && trim($_POST['lat']) !== '';
    $hasLng = isset($_POST['lng']) && trim($_POST['lng']) !== '';

    if ($hasLat xor $hasLng) {
        setFlash('Latitude dan longitude harus diisi lengkap (keduanya).', 'warning');
        header('Location: admin.php');
        exit();
    }

    $lat = $hasLat ? (float) $_POST['lat'] : null;
    $lng = $hasLng ? (float) $_POST['lng'] : null;

    if ($name === '' || $email === '') {
        setFlash('Nama dan email wajib diisi.', 'warning');
        header('Location: admin.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('Format email tidak valid.', 'warning');
        header('Location: admin.php');
        exit();
    }

    if ($role !== 'employee' && $role !== 'admin') {
        setFlash('Role tidak valid.', 'warning');
        header('Location: admin.php');
        exit();
    }

    if ($lat === null || $lng === null) {
        $sql = "INSERT INTO employees (name, email, password, role, location_lat, location_lng) VALUES (?, ?, ?, ?, NULL, NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $email, $password, $role);
    } else {
        $sql = "INSERT INTO employees (name, email, password, role, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdd", $name, $email, $password, $role, $lat, $lng);
    }

    if ($stmt->execute()) {
        setFlash('Karyawan berhasil ditambahkan.', 'success');
    } else {
        // Duplicate email is the most common failure.
        if ($conn->errno === 1062) {
            setFlash('Email sudah terdaftar. Gunakan email lain.', 'warning');
        } else {
            setFlash('Gagal menambahkan karyawan.', 'danger');
        }
    }

    header('Location: admin.php');
    exit();
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
        <h2>Admin Panel</h2>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo e($flash['type']); ?> shadow-sm" role="alert"><?php echo e($flash['message']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Tambah Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="employee">Karyawan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="lat" class="form-label">Latitude Lokasi (opsional)</label>
                                <input type="text" class="form-control" id="lat" name="lat">
                            </div>
                            <div class="mb-3">
                                <label for="lng" class="form-label">Longitude Lokasi (opsional)</label>
                                <input type="text" class="form-control" id="lng" name="lng">
                            </div>
                            <button type="submit" name="add_employee" class="btn btn-primary">Tambah</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Daftar Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($emp = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo e($emp['name']); ?></td>
                                        <td><?php echo e($emp['email']); ?></td>
                                        <td><?php echo ucfirst($emp['role']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5>Laporan Absen Semua Karyawan</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Tanggal</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($att = $attendances->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($att['name']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($att['date'])); ?></td>
                                <td><?php echo $att['check_in'] ? date('H:i:s', strtotime($att['check_in'])) : '-'; ?></td>
                                <td><?php echo $att['check_out'] ? date('H:i:s', strtotime($att['check_out'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
