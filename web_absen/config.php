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

// Start session
session_start();

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
function isLocationAllowed($lat, $lng, $allowedLat, $allowedLng, $radiusKm = 0.001) {
    if ($allowedLat === null || $allowedLng === null) {
        return true; // No location restriction
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
