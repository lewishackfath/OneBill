<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', '3CX CDR Processor'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Australia/Sydney'),
    'session' => [
        'name' => env('SESSION_NAME', 'CDRSESSID'),
        'lifetime' => (int) env('SESSION_LIFETIME', '7200'),
        'secure' => filter_var(env('SESSION_SECURE', 'false'), FILTER_VALIDATE_BOOLEAN),
        'http_only' => filter_var(env('SESSION_HTTP_ONLY', 'true'), FILTER_VALIDATE_BOOLEAN),
        'samesite' => env('SESSION_SAMESITE', 'Lax'),
    ],
];
