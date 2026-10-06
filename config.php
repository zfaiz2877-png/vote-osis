<?php
session_start();

// Security Headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin');

if (file_exists('/.dockerenv')) {
    // Jika script berjalan di DALAM kontainer Docker
    $db_host = 'host.docker.internal'; 
} else {
    // Jika script berjalan di LUAR Docker (XAMPP / Localhost biasa)
    $db_host = 'localhost'; 
}

// Database Config
// $host = 'localhost';
$db   = 'db_voting_osis';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Mencegah SQL Injection
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal. Hubungi Admin.");
    
}

// Fungsi Security
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function checkAdmin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        header('Location: admin.php');
        exit;
    }
}

// Rate Limiting (Anti Brute Force)
function checkRateLimit($action, $max_attempts = 5, $time_window = 900) {
    $key = 'rate_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];
    
    if (time() - $attempts['time'] > $time_window) {
        $attempts = ['count' => 0, 'time' => time()];
    }
    
    $attempts['count']++;
    $_SESSION[$key] = $attempts;
    
    if ($attempts['count'] > $max_attempts) {
        die("Terlalu banyak percobaan. Coba lagi dalam 15 menit.");
    }
}
?>