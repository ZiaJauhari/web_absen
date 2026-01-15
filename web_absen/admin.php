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
$messageType = 'info';

// Handle add employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $role = sanitizeInput($_POST['role']);
    $lat = !empty($_POST['lat']) ? floatval($_POST['lat']) : null;
    $lng = !empty($_POST['lng']) ? floatval($_POST['lng']) : null;

    // Validation
    $errors = [];
    
    if (strlen($name) < 3) {
        $errors[] = 'Nama harus minimal 3 karakter.';
    }
    
    if (!isValidEmail($email)) {
        $errors[] = 'Format email tidak valid.';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password harus minimal 6 karakter.';
    }
    
    if (!in_array($role, ['employee', 'admin'])) {
        $errors[] = 'Role tidak valid.';
    }

    // Check if email already exists
    $sql = "SELECT id FROM employees WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = 'Email sudah terdaftar.';
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO employees (name, email, password, role, location_lat, location_lng) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdd", $name, $email, $hashedPassword, $role, $lat, $lng);
        
        if ($stmt->execute()) {
            $message = 'Karyawan berhasil ditambahkan.';
            $messageType = 'success';
        } else {
            $message = 'Gagal menambahkan karyawan: ' . $conn->error;
            $messageType = 'danger';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'danger';
    }
}

// Handle delete employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_employee'])) {
    $employeeId = intval($_POST['employee_id']);
    
    // Prevent deleting self
    if ($employeeId == $user['id']) {
        $message = 'Anda tidak dapat menghapus akun Anda sendiri.';
        $messageType = 'danger';
    } else {
        $sql = "DELETE FROM employees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employeeId);
        
        if ($stmt->execute()) {
            $message = 'Karyawan berhasil dihapus.';
            $messageType = 'success';
        } else {
            $message = 'Gagal menghapus karyawan.';
            $messageType = 'danger';
        }
    }
}

// Get all employees
$sql = "SELECT * FROM employees ORDER BY name";
$employees = $conn->query($sql);

// Get all attendance with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sql = "SELECT COUNT(*) as total FROM attendance";
$totalResult = $conn->query($sql);
$totalRecords = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);

$sql = "SELECT a.*, e.name FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        ORDER BY a.date DESC, a.check_in DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$attendances = $stmt->get_result();

// Get statistics
$sql = "SELECT 
    COUNT(DISTINCT employee_id) as total_employees_today,
    COUNT(*) as total_attendance_today
    FROM attendance 
    WHERE date = CURDATE()";
$statsResult = $conn->query($sql);
$stats = $statsResult->fetch_assoc();

$sql = "SELECT COUNT(*) as total_employees FROM employees WHERE role = 'employee'";
$totalEmployeesResult = $conn->query($sql);
$totalEmployees = $totalEmployeesResult->fetch_assoc()['total_employees'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <span class="nav-link text-white">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card" style="border-left: 4px solid #10b981;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Karyawan</h6>
                                <h3 class="mb-0"><?php echo $totalEmployees; ?></h3>
                            </div>
                            <div class="text-success" style="font-size: 2.5rem;">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card" style="border-left: 4px solid #3b82f6;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Hadir Hari Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['total_employees_today']; ?></h3>
                            </div>
                            <div class="text-primary" style="font-size: 2.5rem;">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card" style="border-left: 4px solid #f59e0b;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Absen Hari Ini</h6>
                                <h3 class="mb-0"><?php echo $stats['total_attendance_today']; ?></h3>
                            </div>
                            <div class="text-warning" style="font-size: 2.5rem;">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus"></i> Tambah Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user"></i> Nama Lengkap
                                </label>
                                <input type="text" class="form-control" id="name" name="name" required minlength="3">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                <small class="text-muted">Minimal 6 karakter</small>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">
                                    <i class="fas fa-user-tag"></i> Role
                                </label>
                                <select class="form-control" id="role" name="role">
                                    <option value="employee">Karyawan</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="lat" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Latitude Lokasi (opsional)
                                </label>
                                <input type="text" class="form-control" id="lat" name="lat" placeholder="Contoh: -6.208763">
                            </div>
                            <div class="mb-3">
                                <label for="lng" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Longitude Lokasi (opsional)
                                </label>
                                <input type="text" class="form-control" id="lng" name="lng" placeholder="Contoh: 106.845599">
                            </div>
                            <button type="submit" name="add_employee" class="btn btn-primary w-100">
                                <i class="fas fa-plus"></i> Tambah Karyawan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Daftar Karyawan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user"></i> Nama</th>
                                        <th><i class="fas fa-envelope"></i> Email</th>
                                        <th><i class="fas fa-user-tag"></i> Role</th>
                                        <th><i class="fas fa-cog"></i> Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($employees->num_rows > 0): ?>
                                        <?php while ($emp = $employees->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($emp['name']); ?></td>
                                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                                <td>
                                                    <?php if ($emp['role'] == 'admin'): ?>
                                                        <span class="badge bg-primary">
                                                            <i class="fas fa-user-shield"></i> Admin
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-user"></i> Karyawan
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($emp['id'] != $user['id']): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?');">
                                                            <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                                            <button type="submit" name="delete_employee" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <div class="empty-state">
                                                    <div class="empty-state-icon">👥</div>
                                                    <div class="empty-state-text">Belum ada karyawan</div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Laporan Absen Semua Karyawan</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Nama</th>
                                <th><i class="fas fa-calendar"></i> Tanggal</th>
                                <th><i class="fas fa-sign-in-alt"></i> Check In</th>
                                <th><i class="fas fa-sign-out-alt"></i> Check Out</th>
                                <th><i class="fas fa-hourglass-half"></i> Durasi</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($attendances->num_rows > 0): ?>
                                <?php while ($att = $attendances->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($att['name']); ?></td>
                                        <td><?php echo formatDateIndo($att['date']); ?></td>
                                        <td><?php echo $att['check_in'] ? date('H:i:s', strtotime($att['check_in'])) : '-'; ?></td>
                                        <td><?php echo $att['check_out'] ? date('H:i:s', strtotime($att['check_out'])) : '-'; ?></td>
                                        <td>
                                            <?php
                                            if ($att['check_in'] && $att['check_out']) {
                                                $checkIn = new DateTime($att['check_in']);
                                                $checkOut = new DateTime($att['check_out']);
                                                $interval = $checkIn->diff($checkOut);
                                                echo $interval->format('%h jam %i menit');
                                            } else {
                                                echo '-';
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
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">📋</div>
                                            <div class="empty-state-text">Belum ada data absen</div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
