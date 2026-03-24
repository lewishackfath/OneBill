<?php
declare(strict_types=1);

return [
    'host' => (string) env_value('DB_HOST', '127.0.0.1'),
    'port' => (int) env_value('DB_PORT', 3306),
    'name' => (string) env_value('DB_NAME', ''),
    'user' => (string) env_value('DB_USER', ''),
    'pass' => (string) env_value('DB_PASS', ''),
    'charset' => (string) env_value('DB_CHARSET', 'utf8mb4'),
];
