<?php
/* =============================================
   DLMS - Core Configuration
   ============================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path'     => '/',
        'domain'   => 'website domain',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ── Database ── */
define('DB_HOST', '{======SERVER======}');
define('DB_PORT', '3306');
define('DB_NAME', '======DB NAME======);
define('DB_USER', '======USER======);
define('DB_PASS', '======PASSWORD======);

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    /* Set timezone for both PHP and MySQL */
    date_default_timezone_set('Asia/Kolkata');
    $pdo->exec("SET time_zone = '+05:30'");

} catch (PDOException $e) {
    error_log("Connection Error: " . $e->getMessage());
    http_response_code(503);
    if (ob_get_length()) ob_clean();
    require_once __DIR__ . '/db-error.php';
    exit();
}

/* ── CSRF ── */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}

/* ── Flash messages ── */
function flash(string $key, mixed $value = null): mixed {
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }
    $val = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $val;
}

/* ── Redirect helper ── */
function redirect(string $url): never {
    session_write_close();
    header("Location: $url");
    exit;
}

/* ── HTML escape ── */
function e(mixed $v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
