<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';

require_settings_access();

function get_setting_values(array $keys): array
{
    if ($keys === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = db()->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key IN ({$placeholders})");
    $stmt->execute(array_values($keys));
    $rows = $stmt->fetchAll();

    $values = [];
    foreach ($rows as $row) {
        $values[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
    }

    return $values;
}

function upsert_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        'INSERT INTO app_settings (setting_key, setting_value, updated_at)
         VALUES (:setting_key, :setting_value, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );
    $stmt->execute([
        ':setting_key' => $key,
        ':setting_value' => $value,
    ]);
}

$settingKeys = ['app_name', 'default_timezone', 'session_timeout_minutes', 'password_policy_text'];
$stored = get_setting_values($settingKeys);

$form = [
    'app_name' => $stored['app_name'] ?? app_config('name', '3CX CDR Processor'),
    'default_timezone' => $stored['default_timezone'] ?? app_config('timezone', 'Australia/Sydney'),
    'session_timeout_minutes' => $stored['session_timeout_minutes'] ?? (string) max(5, ((int) app_config('session.lifetime', 7200)) / 60),
    'password_policy_text' => $stored['password_policy_text'] ?? 'Use at least 12 characters. Consider a strong passphrase or password manager generated password.',
];

$errors = [];
if (submitted('POST')) {
    verify_csrf();

    $form['app_name'] = trim((string) ($_POST['app_name'] ?? ''));
    $form['default_timezone'] = trim((string) ($_POST['default_timezone'] ?? ''));
    $form['session_timeout_minutes'] = trim((string) ($_POST['session_timeout_minutes'] ?? ''));
    $form['password_policy_text'] = trim((string) ($_POST['password_policy_text'] ?? ''));

    if ($form['app_name'] === '') {
        $errors['app_name'] = 'Application name is required.';
    }
    if ($form['default_timezone'] === '' || !in_array($form['default_timezone'], timezone_identifiers_list(), true)) {
        $errors['default_timezone'] = 'Select a valid PHP timezone.';
    }
    $timeoutMinutes = (int) $form['session_timeout_minutes'];
    if ($timeoutMinutes < 5 || $timeoutMinutes > 1440) {
        $errors['session_timeout_minutes'] = 'Session timeout must be between 5 and 1440 minutes.';
    }

    if ($errors === []) {
        upsert_setting('app_name', $form['app_name']);
        upsert_setting('default_timezone', $form['default_timezone']);
        upsert_setting('session_timeout_minutes', (string) $timeoutMinutes);
        upsert_setting('password_policy_text', $form['password_policy_text']);

        audit_log(auth_user_id(), current_client_id(), 'settings_updated', 'app_settings', null, 'Updated platform settings');
        flash('success', 'Settings updated successfully.');
        redirect('settings/index.php');
    }
}

$pageTitle = 'Settings';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <div class="page-actions">
                <div>
                    <h2>Platform Settings</h2>
                    <p>Manage a small set of global application defaults.</p>
                </div>
            </div>

            <form method="post" class="form-stack">
                <?= csrf_input() ?>
                <div class="form-grid">
                    <label>
                        <span>Application Name</span>
                        <input type="text" name="app_name" value="<?= e($form['app_name']) ?>" required>
                        <?php if (isset($errors['app_name'])): ?><span class="field-error"><?= e($errors['app_name']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span>Default Timezone</span>
                        <input type="text" name="default_timezone" value="<?= e($form['default_timezone']) ?>" list="timezone-options" required>
                        <?php if (isset($errors['default_timezone'])): ?><span class="field-error"><?= e($errors['default_timezone']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span>Session Timeout (minutes)</span>
                        <input type="number" min="5" max="1440" name="session_timeout_minutes" value="<?= e($form['session_timeout_minutes']) ?>" required>
                        <?php if (isset($errors['session_timeout_minutes'])): ?><span class="field-error"><?= e($errors['session_timeout_minutes']) ?></span><?php endif; ?>
                    </label>
                    <label class="full-width">
                        <span>Password Policy Help Text</span>
                        <textarea name="password_policy_text" rows="5"><?= e($form['password_policy_text']) ?></textarea>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button">Save Settings</button>
                </div>
            </form>
        </section>
    </main>
</div>
<datalist id="timezone-options">
    <?php foreach (timezone_identifiers_list() as $tz): ?>
        <option value="<?= e($tz) ?>"></option>
    <?php endforeach; ?>
</datalist>
<?php require APP_PATH . '/includes/footer.php'; ?>
