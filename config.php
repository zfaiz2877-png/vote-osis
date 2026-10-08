<?php
ob_start();

$isForwardedHttps = ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$isHttps = $isForwardedHttps || (($_SERVER['HTTPS'] ?? '') === 'on');
if ($isForwardedHttps) {
    $_SERVER['HTTPS'] = 'on';
}

ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$host = file_exists('/.dockerenv') ? 'host.docker.internal' : 'localhost';
$database = getenv('DB_NAME') ?: 'db_voting_osis';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi database atau hubungi administrator.');
}

function sanitize($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function checkAdmin()
{
    if (($_SESSION['is_admin'] ?? false) !== true) {
        header('Location: admin.php');
        exit;
    }
}

function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 900)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$action) . '_' . hash('sha256', $ip);
    $attempts = $_SESSION[$key] ?? ['count' => 0, 'time' => time()];

    if (time() - $attempts['time'] >= $timeWindow) {
        $attempts = ['count' => 0, 'time' => time()];
    }

    if ($attempts['count'] >= $maxAttempts) {
        return false;
    }

    $attempts['count']++;
    $_SESSION[$key] = $attempts;
    return true;
}