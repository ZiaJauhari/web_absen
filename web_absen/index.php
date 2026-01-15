<?php
include 'config.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] == 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi.';
    } elseif (!isValidEmail($email)) {
        $error = 'Format email tidak valid.';
    } else {
        $sql = "SELECT * FROM employees WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                if ($user['role'] == 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Password salah. Silakan coba lagi.';
            }
        } else {
            $error = 'Email tidak ditemukan. Silakan periksa kembali.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absen Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container login-container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="card-header text-center">
                        <div class="mb-3" style="font-size: 3rem;">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <h3 class="mb-0">Sistem Absen Karyawan</h3>
                        <p class="text-white-50 mb-0 mt-2">Silakan login untuk melanjutkan</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    placeholder="nama@email.com"
                                    required
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                >
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <div class="input-group">
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="password" 
                                        name="password" 
                                        placeholder="Masukkan password"
                                        required
                                    >
                                    <button 
                                        class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword"
                                        style="border-color: #e5e7eb;"
                                    >
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        
                        <div class="mt-4 p-3" style="background: #f0f9ff; border-radius: 10px; border-left: 4px solid #0ea5e9;">
                            <p class="mb-2"><strong><i class="fas fa-info-circle text-info"></i> Demo Account:</strong></p>
                            <p class="mb-1" style="font-size: 0.9rem;">
                                <strong>Admin:</strong> admin@company.com
                            </p>
                            <p class="mb-1" style="font-size: 0.9rem;">
                                <strong>Karyawan:</strong> john@company.com
                            </p>
                            <p class="mb-0" style="font-size: 0.9rem;">
                                <strong>Password:</strong> password
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <p class="text-white">
                        <i class="fas fa-shield-alt"></i> Sistem Absen Karyawan v1.0
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!email || !password) {
                e.preventDefault();
                alert('Email dan password harus diisi.');
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>
