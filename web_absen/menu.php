<?php
include 'config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Kolabo App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="menu-page">
    <div class="menu-container">
        <!-- Notification Banner -->
        <div class="notification-banner">
            <i class="fas fa-volume-up"></i>
            <marquee>Hai, boleh biru | DRESS CODE TGL 01/15/26 : Besok dresscode biru</marquee>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <!-- Row 1 -->
            <a href="#" class="menu-item menu-large">
                <i class="fas fa-briefcase"></i>
                <span>PROJECT & TASK</span>
            </a>
            <div class="menu-column">
                <a href="reports.php" class="menu-item menu-medium">
                    <i class="fas fa-user-clock"></i>
                    <span>REQUEST / ATTENDANCE HISTORY</span>
                </a>
                <div class="menu-row">
                    <a href="#" class="menu-item menu-small">
                        <i class="fas fa-user-slash"></i>
                        <span>LEAVE</span>
                    </a>
                    <a href="#" class="menu-item menu-small">
                        <i class="fas fa-home"></i>
                        <span>WFH/WFA</span>
                    </a>
                </div>
            </div>

            <!-- Row 2 -->
            <a href="#" class="menu-item menu-large">
                <i class="fas fa-share-square"></i>
                <span>KOLABO MEET</span>
            </a>
            <a href="#" class="menu-item menu-large">
                <i class="fas fa-cloud"></i>
                <span>KOLABO CLOUD</span>
            </a>

            <!-- Row 3 -->
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-dollar-sign"></i>
                <span>REIMBURSE</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-piggy-bank"></i>
                <span>SAVINGS</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-calculator"></i>
                <span>EXPENSE</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-receipt"></i>
                <span>KASBON</span>
            </a>

            <!-- Row 4 -->
            <a href="#" class="menu-item menu-full">
                <i class="fas fa-file-alt"></i>
                <span>KOLABO FORM</span>
                <span class="badge-new">NEW</span>
            </a>

            <!-- Row 5 -->
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-qrcode"></i>
                <span>ASSETS</span>
                <span class="badge-new">NEW</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-sticky-note"></i>
                <span>NOTES</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-users"></i>
                <span>TEAM</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-id-card"></i>
                <span>ID CARD</span>
            </a>

            <!-- Row 6 -->
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-calendar-alt"></i>
                <span>HOLIDAY</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-calendar"></i>
                <span>CALENDAR</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-money-check"></i>
                <span>PAY SLIP</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-clock"></i>
                <span>OVERTIME</span>
            </a>

            <!-- Row 7 -->
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-handshake"></i>
                <span>MEETING</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-lock"></i>
                <span>PASSWORD</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-bell"></i>
                <span>NOTICES: 2</span>
            </a>
            <a href="#" class="menu-item menu-small">
                <i class="fas fa-user"></i>
                <span>PROFILE</span>
            </a>
        </div>

        <!-- Footer Section -->
        <div class="menu-footer">
            <div class="footer-links">
                <a href="#" class="footer-link">
                    <i class="fas fa-list-check"></i>
                    <span>RULES</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-info-circle"></i>
                    <span>ABOUT US</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-file-contract"></i>
                    <span>TNC</span>
                </a>
                <a href="#" class="footer-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>POLICY</span>
                </a>
            </div>
            <a href="logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>LOG OUT</span>
            </a>
        </div>

        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-home"></i>
            </a>
            <a href="menu.php" class="nav-item nav-center active">
                <div class="center-icon">
                    <i class="fas fa-th-large"></i>
                </div>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-comment"></i>
            </a>
        </nav>
    </div>

    <!-- Floating Action Button -->
    <button class="fab-btn">
        <i class="fas fa-robot"></i>
    </button>
</body>
</html>
