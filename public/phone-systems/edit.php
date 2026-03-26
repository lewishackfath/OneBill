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
$id = (int) ($_GET['id'] ?? 0);
$phoneSystem = $repo->findVisibleById($id, $authUser);
if ($phoneSystem === null) {
    http_response_code(404);
    exit('Phone system not found.');
}

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
        'timezone' => trim((string) ($_POST['timezone'] ?? 'Australia/Sydney')),
        'status' => (string) ($_POST['status'] ?? 'active'),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    $replacePassword = ((string) ($_POST['replace_api_password'] ?? '0')) === '1';
    $replaceToken = ((string) ($_POST['replace_api_token'] ?? '0')) === '1';
    if ($replacePassword) {
        $data['api_password'] = (string) ($_POST['api_password'] ?? '');
    }
    if ($replaceToken) {
        $data['api_token'] = trim((string) ($_POST['api_token'] ?? ''));
    }

    $errors = [];
    if ($data['client_id'] <= 0 || !in_array($data['client_id'], $allowedClientIds, true)) {
        $errors['client_id'] = 'Select a valid client.';
    }
    if ($data['system_name'] === '') {
        $errors['system_name'] = 'System name is required.';
    }
    if ($data['system_code'] === '') {
        $errors['system_code'] = 'System code is required.';
    } elseif ($data['client_id'] > 0 && $repo->codeExistsForClient($data['client_id'], $data['system_code'], $id)) {
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
    if ($data['auth_type'] === 'basic' && $replacePassword && $data['api_password'] === '') {
        $errors['api_password'] = 'Enter the replacement API password.';
    }
    if ($data['auth_type'] === 'bearer' && $replaceToken && ($data['api_token'] ?? '') === '') {
        $errors['api_token'] = 'Enter the replacement API token.';
    }

    if ($errors !== []) {
        redirect_with_errors('phone-systems/edit.php?id=' . $id, $errors, $_POST);
    }

    $repo->update($id, $data);
    audit_log(auth_user_id(), $data['client_id'], 'phone_system_updated', 'phone_system', (string) $id, 'Updated phone system ' . $data['system_name'], [
        'auth_type' => $data['auth_type'],
        'system_code' => $data['system_code'],
        'credentials_rotated' => [
            'password' => $replacePassword,
            'token' => $replaceToken,
        ],
    ]);
    flash('success', 'Phone system updated successfully.');
    redirect('phone-systems/index.php');
}

$pageTitle = 'Edit Phone System';
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
                    <h2>Edit 3CX Phone System</h2>
                    <p>Update connection settings, credentials, and tenant mapping.</p>
                </div>
                <form method="post" action="<?= e(base_url('phone-systems/test-connection.php')) ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= (int) $phoneSystem['id'] ?>">
                    <button type="submit" class="button button--secondary">Test Connection</button>
                </form>
            </div>

            <form method="post" class="form-stack">
                <?= csrf_input() ?>

                <div class="form-section">
                    <h3>System Details</h3>
                    <div class="form-grid">
                        <label>
                            <span>Client</span>
                            <select name="client_id" required>
                                <?php $selectedClientId = (int) old_input('client_id', (string) $phoneSystem['client_id']); ?>
                                <?php foreach ($availableClients as $client): ?>
                                    <option value="<?= (int) $client['id'] ?>" <?= ($selectedClientId === (int) $client['id']) ? 'selected' : '' ?>>
                                        <?= e((string) $client['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($message = field_error($errors, 'client_id')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>System Name</span>
                            <input type="text" name="system_name" value="<?= e((string) old_input('system_name', (string) $phoneSystem['system_name'])) ?>" required>
                            <?php if ($message = field_error($errors, 'system_name')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>System Code</span>
                            <input type="text" name="system_code" value="<?= e((string) old_input('system_code', (string) $phoneSystem['system_code'])) ?>" required>
                            <?php if ($message = field_error($errors, 'system_code')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Base URL</span>
                            <input type="url" name="base_url" value="<?= e((string) old_input('base_url', (string) $phoneSystem['base_url'])) ?>" required>
                            <?php if ($message = field_error($errors, 'base_url')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Timezone</span>
                            <input type="text" name="timezone" value="<?= e((string) old_input('timezone', (string) $phoneSystem['timezone'])) ?>" required>
                            <?php if ($message = field_error($errors, 'timezone')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Status</span>
                            <select name="status">
                                <?php $status = (string) old_input('status', (string) $phoneSystem['status']); ?>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Authentication</h3>
                    <div class="form-grid">
                        <?php $authType = (string) old_input('auth_type', (string) $phoneSystem['auth_type']); ?>
                        <label>
                            <span>Auth Type</span>
                            <select name="auth_type">
                                <option value="basic" <?= $authType === 'basic' ? 'selected' : '' ?>>Basic</option>
                                <option value="bearer" <?= $authType === 'bearer' ? 'selected' : '' ?>>Bearer Token</option>
                            </select>
                            <?php if ($message = field_error($errors, 'auth_type')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>API Username</span>
                            <input type="text" name="api_username" value="<?= e((string) old_input('api_username', (string) $phoneSystem['api_username'])) ?>">
                            <?php if ($message = field_error($errors, 'api_username')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Replace API Password?</span>
                            <select name="replace_api_password">
                                <option value="0" <?= old_input('replace_api_password', '0') === '0' ? 'selected' : '' ?>>Keep existing</option>
                                <option value="1" <?= old_input('replace_api_password') === '1' ? 'selected' : '' ?>>Replace password</option>
                            </select>
                        </label>
                        <label>
                            <span>New API Password</span>
                            <input type="password" name="api_password" value="">
                            <?php if ($message = field_error($errors, 'api_password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Replace API Token?</span>
                            <select name="replace_api_token">
                                <option value="0" <?= old_input('replace_api_token', '0') === '0' ? 'selected' : '' ?>>Keep existing</option>
                                <option value="1" <?= old_input('replace_api_token') === '1' ? 'selected' : '' ?>>Replace token</option>
                            </select>
                        </label>
                        <label>
                            <span>New API Token</span>
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
                            <textarea name="notes" rows="5"><?= e((string) old_input('notes', (string) ($phoneSystem['notes'] ?? ''))) ?></textarea>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">Save Changes</button>
                    <a class="button button--secondary" href="<?= e(base_url('phone-systems/index.php')) ?>">Cancel</a>
                </div>
            </form>
        </section>
    </main>
</div>
<?php consume_old_input(); ?>
<?php require APP_PATH . '/includes/footer.php'; ?>
