<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/PhoneSystemRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

require_phone_system_admin_access();

$authUser = auth_user();
$repo = new PhoneSystemRepository();
$clientRepo = new ClientRepository();
$availableClients = $clientRepo->getOptionsForUser($authUser);
$allowedClientIds = array_column($availableClients, 'id');
$errors = validation_errors();

if (submitted('POST')) {
    verify_csrf();

    $data = [
        'client_id' => (int) ($_POST['client_id'] ?? 0),
        'system_name' => trim((string) ($_POST['system_name'] ?? '')),
        'system_code' => trim((string) ($_POST['system_code'] ?? '')),
        'base_url' => trim((string) ($_POST['base_url'] ?? '')),
        'auth_type' => (string) ($_POST['auth_type'] ?? 'basic'),
        'api_username' => trim((string) ($_POST['api_username'] ?? '')),
        'api_password' => (string) ($_POST['api_password'] ?? ''),
        'api_token' => trim((string) ($_POST['api_token'] ?? '')),
        'timezone' => trim((string) ($_POST['timezone'] ?? 'Australia/Sydney')),
        'status' => (string) ($_POST['status'] ?? 'active'),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    $errors = [];
    if ($data['client_id'] <= 0 || !in_array($data['client_id'], $allowedClientIds, true)) {
        $errors['client_id'] = 'Select a valid client.';
    }
    if ($data['system_name'] === '') {
        $errors['system_name'] = 'System name is required.';
    }
    if ($data['system_code'] === '') {
        $errors['system_code'] = 'System code is required.';
    } elseif ($data['client_id'] > 0 && $repo->codeExistsForClient($data['client_id'], $data['system_code'])) {
        $errors['system_code'] = 'That system code is already in use for this client.';
    }
    if ($data['base_url'] === '') {
        $errors['base_url'] = 'Base URL is required.';
    } elseif (!filter_var($data['base_url'], FILTER_VALIDATE_URL)) {
        $errors['base_url'] = 'Enter a valid base URL.';
    }
    if (!in_array($data['auth_type'], ['basic', 'bearer'], true)) {
        $errors['auth_type'] = 'Select a valid authentication method.';
    }
    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        $errors['status'] = 'Select a valid status.';
    }
    if ($data['timezone'] === '') {
        $errors['timezone'] = 'Timezone is required.';
    }
    if ($data['auth_type'] === 'basic' && $data['api_username'] === '') {
        $errors['api_username'] = 'API username is required for basic auth.';
    }
    if ($data['auth_type'] === 'basic' && $data['api_password'] === '') {
        $errors['api_password'] = 'API password is required for basic auth.';
    }
    if ($data['auth_type'] === 'bearer' && $data['api_token'] === '') {
        $errors['api_token'] = 'API token is required for bearer auth.';
    }

    if ($errors !== []) {
        redirect_with_errors('phone-systems/create.php', $errors, $_POST);
    }

    $id = $repo->create($data);
    audit_log(auth_user_id(), $data['client_id'], 'phone_system_created', 'phone_system', (string) $id, 'Created phone system ' . $data['system_name'], [
        'auth_type' => $data['auth_type'],
        'system_code' => $data['system_code'],
    ]);
    flash('success', 'Phone system created successfully.');
    redirect('phone-systems/index.php');
}

$pageTitle = 'Add Phone System';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <h2>New 3CX Phone System</h2>
            <p>Create a client-scoped 3CX connection record ready for future CDR imports.</p>

            <form method="post" class="form-stack">
                <?= csrf_input() ?>

                <div class="form-section">
                    <h3>System Details</h3>
                    <div class="form-grid">
                        <label>
                            <span>Client</span>
                            <select name="client_id" required>
                                <option value="">Select client</option>
                                <?php foreach ($availableClients as $client): ?>
                                    <option value="<?= (int) $client['id'] ?>" <?= ((int) old_input('client_id', current_client_id() ?? 0) === (int) $client['id']) ? 'selected' : '' ?>>
                                        <?= e((string) $client['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($message = field_error($errors, 'client_id')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>System Name</span>
                            <input type="text" name="system_name" value="<?= e((string) old_input('system_name')) ?>" required>
                            <?php if ($message = field_error($errors, 'system_name')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>System Code</span>
                            <input type="text" name="system_code" value="<?= e((string) old_input('system_code')) ?>" required>
                            <?php if ($message = field_error($errors, 'system_code')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Base URL</span>
                            <input type="url" name="base_url" value="<?= e((string) old_input('base_url')) ?>" placeholder="https://pbx.example.com" required>
                            <?php if ($message = field_error($errors, 'base_url')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Timezone</span>
                            <input type="text" name="timezone" value="<?= e((string) old_input('timezone', 'Australia/Sydney')) ?>" required>
                            <?php if ($message = field_error($errors, 'timezone')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Status</span>
                            <select name="status">
                                <option value="active" <?= old_input('status', 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= old_input('status') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Authentication</h3>
                    <div class="form-grid">
                        <label>
                            <span>Auth Type</span>
                            <select name="auth_type">
                                <option value="basic" <?= old_input('auth_type', 'basic') === 'basic' ? 'selected' : '' ?>>Basic</option>
                                <option value="bearer" <?= old_input('auth_type') === 'bearer' ? 'selected' : '' ?>>Bearer Token</option>
                            </select>
                            <?php if ($message = field_error($errors, 'auth_type')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>API Username</span>
                            <input type="text" name="api_username" value="<?= e((string) old_input('api_username')) ?>">
                            <?php if ($message = field_error($errors, 'api_username')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>API Password</span>
                            <input type="password" name="api_password" value="">
                            <?php if ($message = field_error($errors, 'api_password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>API Token</span>
                            <input type="password" name="api_token" value="">
                            <?php if ($message = field_error($errors, 'api_token')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Notes</h3>
                    <div class="form-grid">
                        <label class="full-width">
                            <span>Internal Notes</span>
                            <textarea name="notes" rows="5"><?= e((string) old_input('notes')) ?></textarea>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">Create Phone System</button>
                    <a class="button button--secondary" href="<?= e(base_url('phone-systems/index.php')) ?>">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</div>
<?php consume_old_input(); ?>
<?php require APP_PATH . '/includes/footer.php'; ?>
