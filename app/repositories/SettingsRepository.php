<?php
declare(strict_types=1);

class SettingsRepository
{
    public function allIndexed(): array
    {
        $stmt = db()->query('SELECT setting_key, setting_value FROM app_settings ORDER BY setting_key ASC');
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function upsert(string $key, ?string $value): void
    {
        $stmt = db()->prepare('
            INSERT INTO app_settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => $value,
        ]);
    }
}
