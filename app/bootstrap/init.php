<?php
declare(strict_types=1);

$app = require __DIR__ . '/../config/app.php';
date_default_timezone_set($app['timezone']);

require_once __DIR__ . '/../helpers/url_helper.php';
require_once __DIR__ . '/../helpers/flash_helper.php';
require_once __DIR__ . '/../helpers/csrf_helper.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/client_helper.php';
require_once __DIR__ . '/../helpers/audit_helper.php';
require_once __DIR__ . '/../helpers/settings_helper.php';

$dbConfig = require __DIR__ . '/../config/database.php';

function db(): PDO
{
    static $pdo = null;
    global $dbConfig;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['name'],
        $dbConfig['charset']
    );

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    global $app;
    if ($key === null) {
        return $app;
    }

    $segments = explode('.', $key);
    $value = $app;
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function boot_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name((string) app_config('session.name', 'cdr_processor_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (bool) app_config('session.cookie_secure', false),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    if (!isset($_SESSION['_created_at'])) {
        $_SESSION['_created_at'] = time();
    }

    if (!isset($_SESSION['_last_activity_at'])) {
        $_SESSION['_last_activity_at'] = time();
    }

    enforce_session_security();
}

function enforce_session_security(): void
{
    $timeoutMinutes = (int) app_setting('session_timeout_minutes', (string) app_config('session.timeout_minutes', 60));
    $absoluteTimeoutMinutes = (int) app_setting('session_absolute_timeout_minutes', (string) app_config('session.absolute_timeout_minutes', 480));
    $now = time();
    $expiredReason = null;

    if (!empty($_SESSION['user']) && $timeoutMinutes > 0) {
        $idleAge = $now - (int) ($_SESSION['_last_activity_at'] ?? $now);
        if ($idleAge > ($timeoutMinutes * 60)) {
            $expiredReason = 'idle';
        }
    }

    if ($expiredReason === null && !empty($_SESSION['user']) && $absoluteTimeoutMinutes > 0) {
        $absoluteAge = $now - (int) ($_SESSION['_created_at'] ?? $now);
        if ($absoluteAge > ($absoluteTimeoutMinutes * 60)) {
            $expiredReason = 'absolute';
        }
    }

    if ($expiredReason !== null) {
        $userId = $_SESSION['user']['id'] ?? null;
        session_unset();
        session_destroy();

        session_name((string) app_config('session.name', 'cdr_processor_session'));
        session_start();
        flash('warning', 'Your session expired. Please sign in again.');
        header('Location: ' . base_url('/expired.php?reason=' . urlencode($expiredReason)));
        exit;
    }

    $_SESSION['_last_activity_at'] = $now;
}

set_exception_handler(function (Throwable $e): void {
    http_response_code(500);
    $message = app_config('debug', false) ? $e->getMessage() : 'An unexpected error occurred.';
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Application Error</title>';
    echo '<link rel="stylesheet" href="' . e(asset_url('/assets/css/app.css')) . '"></head><body class="app-shell">';
    echo '<main class="container narrow"><div class="card"><h1>Application Error</h1><p>' . e($message) . '</p></div></main></body></html>';
    exit;
});

boot_session();

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/ClientRepository.php';
require_once __DIR__ . '/../repositories/RoleRepository.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';
require_once __DIR__ . '/../repositories/SettingsRepository.php';
