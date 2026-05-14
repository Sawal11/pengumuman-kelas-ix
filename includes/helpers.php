<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function setting(string $key, ?string $default = null): ?string
{
    $stmt = pdo()->prepare('SELECT setting_value FROM settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute(['setting_key' => $key]);
    $value = $stmt->fetchColumn();

    return $value === false ? $default : (string) $value;
}

function save_setting(string $key, string $value): void
{
    $stmt = pdo()->prepare(
        'INSERT INTO settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
    ]);
}

function announcement_datetime(): DateTimeImmutable
{
    $raw = setting('announcement_datetime', date('Y-m-d H:i:s', strtotime('+1 day')));
    return new DateTimeImmutable((string) $raw, new DateTimeZone(APP_TIMEZONE));
}

function announcement_is_open(): bool
{
    $now = new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE));
    return $now >= announcement_datetime();
}

function drama_modes(): array
{
    return [
        'Normal',
        'Drama Ringan',
        'Drama Sedang',
        'Drama Lucu',
        'Drama Super Tegang',
    ];
}

function drama_duration_defaults(): array
{
    return [
        'Normal' => 10,
        'Drama Ringan' => 30,
        'Drama Sedang' => 60,
        'Drama Lucu' => 90,
        'Drama Super Tegang' => 180,
    ];
}

function drama_duration_setting_key(string $mode): string
{
    $keys = [
        'Normal' => 'drama_duration_normal',
        'Drama Ringan' => 'drama_duration_ringan',
        'Drama Sedang' => 'drama_duration_sedang',
        'Drama Lucu' => 'drama_duration_lucu',
        'Drama Super Tegang' => 'drama_duration_super_tegang',
    ];

    return $keys[$mode] ?? 'drama_duration_normal';
}

function drama_durations_seconds(): array
{
    $durations = [];
    foreach (drama_duration_defaults() as $mode => $defaultSeconds) {
        $raw = setting(drama_duration_setting_key($mode), (string) $defaultSeconds);
        $seconds = filter_var($raw, FILTER_VALIDATE_INT);
        if ($seconds === false || $seconds < 1 || $seconds > 1800) {
            $seconds = $defaultSeconds;
        }
        $durations[$mode] = $seconds;
    }

    return $durations;
}

function drama_durations_milliseconds(): array
{
    $durations = [];
    foreach (drama_durations_seconds() as $mode => $seconds) {
        $durations[$mode] = $seconds * 1000;
    }

    return $durations;
}

function normalize_drama_mode(?string $mode): string
{
    $mode = trim((string) $mode);
    if ($mode === 'Drama Super Tegang tapi Tetap Aman') {
        return 'Drama Super Tegang';
    }

    return in_array($mode, drama_modes(), true) ? $mode : 'Normal';
}

function graduation_status(?string $status = null): string
{
    return 'LULUS';
}

function format_date_id(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $time = strtotime($date);
    if ($time === false) {
        return '-';
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    return date('j', $time) . ' ' . $months[(int) date('n', $time)] . ' ' . date('Y', $time);
}

function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_secure_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_secure_session();

    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(419);
        exit('Sesi formulir tidak valid. Silakan muat ulang halaman.');
    }
}

function json_response(array $payload, int $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function client_ip(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = (string) $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $value = trim(explode(',', $value)[0]);
            }
            return substr($value, 0, 45);
        }
    }

    return '-';
}

function app_url(string $path = ''): string
{
    $base = trim(APP_BASE_URL);
    if ($base !== '') {
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }

    return $path;
}
