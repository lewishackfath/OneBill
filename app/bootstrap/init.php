<?php

declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}
define('APP_BOOTSTRAPPED', true);

define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

require_once APP_PATH . '/helpers/url_helper.php';
require_once APP_PATH . '/helpers/flash_helper.php';
require_once APP_PATH . '/helpers/csrf_helper.php';
require_once APP_PATH . '/helpers/auth_helper.php';
require_once APP_PATH . '/helpers/audit_helper.php';
require_once APP_PATH . '/helpers/client_helper.php';
require_once APP_PATH . '/helpers/validation_helper.php';

load_env_file(BASE_PATH . '/.env');

$appConfig = require APP_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

configure_error_reporting((bool) $appConfig['debug']);
configure_session($appConfig['session']);

$GLOBALS['app_config'] = $appConfig;
$GLOBALS['db'] = make_pdo(require APP_PATH . '/config/database.php');

if (is_logged_in()) {
    refresh_authenticated_user_session();

    if (is_logged_in()) {
        initialise_current_client_context();
    }
}

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function db(): PDO
{
    return $GLOBALS['db'];
}

function make_pdo(array $config): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    return new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function configure_error_reporting(bool $debug): void
{
    if ($debug) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        return;
    }

    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

function configure_session(array $sessionConfig): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name((string) ($sessionConfig['name'] ?? 'CDRSESSID'));
    session_set_cookie_params([
        'lifetime' => (int) ($sessionConfig['lifetime'] ?? 7200),
        'path' => '/',
        'domain' => '',
        'secure' => (bool) ($sessionConfig['secure'] ?? false),
        'httponly' => (bool) ($sessionConfig['http_only'] ?? true),
        'samesite' => (string) ($sessionConfig['samesite'] ?? 'Lax'),
    ]);

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', ((bool) ($sessionConfig['http_only'] ?? true)) ? '1' : '0');

    session_start();

    $timeout = (int) ($sessionConfig['lifetime'] ?? 7200);
    $lastActivity = $_SESSION['_last_activity'] ?? time();
    if ((time() - (int) $lastActivity) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['_last_activity'] = time();
}
