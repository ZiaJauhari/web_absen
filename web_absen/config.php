<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'web_absen';

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Start session
session_start();

function setFlash(string $message, string $type = 'info'): void {
    $_SESSION['_flash'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function getFlash(): ?array {
    if (!isset($_SESSION['_flash'])) {
        return null;
    }

    $flash = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $flash;
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user
function getCurrentUser() {
    global $conn;
    if (!isLoggedIn()) return null;

    $id = $_SESSION['user_id'];
    $sql = "SELECT * FROM employees WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to check if location is within allowed area
function isLocationAllowed($lat, $lng, $allowedLat, $allowedLng, $radiusKm = 0.1) {
    if ($allowedLat === null || $allowedLng === null || $allowedLat === '' || $allowedLng === '') {
        return true; // No location restriction
    }

    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }

    $earthRadius = 6371; // km

    $latDelta = deg2rad($lat - $allowedLat);
    $lngDelta = deg2rad($lng - $allowedLng);

    $a = sin($latDelta/2) * sin($latDelta/2) +
         cos(deg2rad($allowedLat)) * cos(deg2rad($lat)) *
         sin($lngDelta/2) * sin($lngDelta/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    $distance = $earthRadius * $c;

    return $distance <= $radiusKm;
}
?>
