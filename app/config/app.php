<?php
declare(strict_types=1);

function env_value(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded) {
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
                    $value = stripcslashes(substr($value, 1, -1));
                }

                if (getenv($name) === false) {
                    putenv($name . '=' . $value);
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        $loaded = true;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $lower = strtolower((string) $value);
    return match ($lower) {
        'true' => true,
        'false' => false,
        'null' => null,
        default => $value,
    };
}

return [
    'name' => (string) env_value('APP_NAME', '3CX CDR Processor'),
    'env' => (string) env_value('APP_ENV', 'production'),
    'debug' => (bool) env_value('APP_DEBUG', false),
    'url' => rtrim((string) env_value('APP_URL', ''), '/'),
    'timezone' => 'Australia/Sydney',
    'session' => [
        'name' => (string) env_value('SESSION_NAME', 'cdr_processor_session'),
        'timeout_minutes' => (int) env_value('SESSION_TIMEOUT_MINUTES', 60),
        'absolute_timeout_minutes' => (int) env_value('SESSION_ABSOLUTE_TIMEOUT_MINUTES', 480),
        'cookie_secure' => (bool) env_value('SESSION_COOKIE_SECURE', false),
    ],
];
