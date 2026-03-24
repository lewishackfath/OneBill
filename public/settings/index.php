<?php
declare(strict_types=1);
$title = 'Settings';
require_once dirname(__DIR__, 2) . '/app/includes/header.php';
require_role(['super_admin', 'platform_admin']);

$settingsRepo = new SettingsRepository();
$current = $settingsRepo->allIndexed();
$defaults = [
    'application_name' => app_config('name', '3CX CDR Processor'),
    'default_timezone' => 'Australia/Sydney',
    'session_timeout_minutes' => (string) app_config('session.timeout_minutes', 60),
    'session_absolute_timeout_minutes' => (string) app_config('session.absolute_timeout_minutes', 480),
    'password_policy_text' => 'Minimum 12 characters recommended.',
    'future_3cx_defaults' => '',
    'future_connectwise_defaults' => '',
];
$values = array_merge($defaults, $current);

if (is_post()) {
    validate_csrf();

    $updates = [
        'application_name' => trim((string) ($_POST['application_name'] ?? '')),
        'default_timezone' => trim((string) ($_POST['default_timezone'] ?? 'Australia/Sydney')),
        'session_timeout_minutes' => trim((string) ($_POST['session_timeout_minutes'] ?? '60')),
        'session_absolute_timeout_minutes' => trim((string) ($_POST['session_absolute_timeout_minutes'] ?? '480')),
        'password_policy_text' => trim((string) ($_POST['password_policy_text'] ?? '')),
        'future_3cx_defaults' => trim((string) ($_POST['future_3cx_defaults'] ?? '')),
        'future_connectwise_defaults' => trim((string) ($_POST['future_connectwise_defaults'] ?? '')),
    ];

    foreach ($updates as $key => $value) {
        $settingsRepo->upsert($key, $value);
    }

    audit_log((int) auth_user()['id'], current_client_id(), 'settings.update', 'settings', null, 'Updated application settings', ['keys' => array_keys($updates)]);
    flash('success', 'Settings saved successfully.');
    redirect('/settings/index.php');
}
?>
<section class="card">
    <h2>Platform Settings</h2>
    <form method="post" class="form-grid">
        <?= csrf_input() ?>
        <div class="form-row">
            <label for="application_name">Application Name</label>
            <input type="text" id="application_name" name="application_name" value="<?= e($values['application_name']) ?>" required>
        </div>
        <div class="form-row">
            <label for="default_timezone">Default Timezone</label>
            <input type="text" id="default_timezone" name="default_timezone" value="<?= e($values['default_timezone']) ?>" required>
        </div>
        <div class="form-row">
            <label for="session_timeout_minutes">Session Timeout (minutes)</label>
            <input type="number" min="5" step="1" id="session_timeout_minutes" name="session_timeout_minutes" value="<?= e($values['session_timeout_minutes']) ?>" required>
        </div>
        <div class="form-row">
            <label for="session_absolute_timeout_minutes">Absolute Session Lifetime (minutes)</label>
            <input type="number" min="15" step="1" id="session_absolute_timeout_minutes" name="session_absolute_timeout_minutes" value="<?= e($values['session_absolute_timeout_minutes']) ?>" required>
        </div>
        <div class="form-row span-2">
            <label for="password_policy_text">Password Policy Help Text</label>
            <textarea id="password_policy_text" name="password_policy_text" rows="4"><?= e($values['password_policy_text']) ?></textarea>
        </div>
        <div class="form-row span-2">
            <label for="future_3cx_defaults">3CX Defaults Placeholder</label>
            <textarea id="future_3cx_defaults" name="future_3cx_defaults" rows="4"><?= e($values['future_3cx_defaults']) ?></textarea>
        </div>
        <div class="form-row span-2">
            <label for="future_connectwise_defaults">ConnectWise Defaults Placeholder</label>
            <textarea id="future_connectwise_defaults" name="future_connectwise_defaults" rows="4"><?= e($values['future_connectwise_defaults']) ?></textarea>
        </div>
        <div class="form-row span-2">
            <button class="button" type="submit">Save Settings</button>
        </div>
    </form>
</section>
<?php require_once dirname(__DIR__, 2) . '/app/includes/footer.php'; ?>
