<?php
declare(strict_types=1);

function app_settings(): array
{
    static $settings = null;
    if (is_array($settings)) {
        return $settings;
    }

    $settings = [];
    try {
        $stmt = db()->query('SELECT setting_key, setting_value FROM app_settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $settings = [];
    }

    return $settings;
}

function app_setting(string $key, mixed $default = null): mixed
{
    $settings = app_settings();
    return $settings[$key] ?? $default;
}
