<?php
/*
|--------------------------------------------------------------------------
| KONFIGURASI APLIKASI
|--------------------------------------------------------------------------
| Kredensial dibaca dari environment variable (atau file .env sederhana)
| dengan fallback ke nilai default lama agar backward compatible untuk
| lingkungan pengembangan lokal (XAMPP/Laragon).
*/

// --- Loader .env sederhana (opsional, tanpa dependency) ---
if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            // hapus tanda kutip pembungkus jika ada
            $value = trim($value, "\"'");
            if ($key !== '' && getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

load_env_file(dirname(__DIR__) . '/.env');

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $val = getenv($key);
        if ($val === false) {
            return $default;
        }
        // normalisasi boolean
        $lower = strtolower($val);
        if ($lower === 'true')  return true;
        if ($lower === 'false') return false;
        return $val;
    }
}

// Mode aplikasi: 'production' atau 'development'
if (!defined('APP_ENV')) {
    define('APP_ENV', env('APP_ENV', 'production'));
}

// Base URL untuk link di view (otomatis deteksi host)
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    define('BASE_URL', $scheme . '://' . $host);
}

// Konfigurasi database (dari env, fallback default lokal)
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'sklipci'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Helper koneksi (mysqli)
function db(): mysqli
{
    static $conn;

    if ($conn === null) {
        // Pastikan ekstensi mysqli tersedia (wajib untuk koneksi DB).
        if (!class_exists('mysqli')) {
            error_log('[DB] Ekstensi PHP "mysqli" tidak aktif.');
            http_response_code(503);
            die('Konfigurasi server belum lengkap: ekstensi PHP "mysqli" belum diaktifkan. '
                . 'Aktifkan baris extension=mysqli pada php.ini lalu restart server.');
        }

        // Aktifkan mode exception agar error query bisa ditangani dengan try/catch
        // dan tidak menghasilkan warning bocor ke output (jika fungsi tersedia).
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        }

        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            // Fallback bila mode exception tidak aktif (mysqli_report tidak tersedia).
            if ($conn->connect_errno) {
                throw new \RuntimeException($conn->connect_error ?? 'connect error');
            }
            $conn->set_charset('utf8mb4');
        } catch (\Throwable $e) {
            // Jangan bocorkan detail koneksi ke pengguna.
            error_log('[DB] Koneksi gagal: ' . $e->getMessage());
            http_response_code(503);
            if (APP_ENV !== 'production') {
                die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }
            die('Layanan sedang tidak tersedia. Silakan coba beberapa saat lagi.');
        }
    }

    return $conn;
}

/*
|--------------------------------------------------------------------------
| HELPER KEAMANAN (CSRF + ESCAPING)
|--------------------------------------------------------------------------
| Tersedia global agar bisa dipakai router, controller, dan view.
| Membutuhkan session aktif sebelum dipanggil.
*/

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify(): bool
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $real = $_SESSION['_csrf'] ?? '';
        return $real !== '' && is_string($sent) && hash_equals($real, $sent);
    }
}

if (!function_exists('e')) {
    // Escape output untuk mencegah XSS
    function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('safe_redirect')) {
    /**
     * Redirect hanya ke URL internal (mencegah open redirect / header injection).
     */
    function safe_redirect(?string $target, string $fallback): void
    {
        $fallbackUrl = BASE_URL . $fallback;
        if (is_string($target) && $target !== '' && str_starts_with($target, BASE_URL)) {
            // buang karakter kontrol untuk mencegah header injection
            $clean = preg_replace('/[\r\n].*$/s', '', $target);
            header('Location: ' . $clean);
        } else {
            header('Location: ' . $fallbackUrl);
        }
        exit;
    }
    }
