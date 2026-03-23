<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

$authUser = auth_user();
$clientRepo = new ClientRepository();

if (!$clientRepo->canManageClients($authUser)) {
    http_response_code(403);
    exit('Forbidden');
}

$clientId = (int) ($_GET['id'] ?? 0);
$client = $clientRepo->findById($clientId);
if ($client === null) {
    http_response_code(404);
    exit('Client not found.');
}

$errors = validation_errors();

if (submitted('POST')) {
    verify_csrf();

    $data = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'code' => trim((string) ($_POST['code'] ?? '')),
        'status' => (string) ($_POST['status'] ?? 'active'),
        'timezone' => trim((string) ($_POST['timezone'] ?? 'Australia/Sydney')),
        'contact_name' => trim((string) ($_POST['contact_name'] ?? '')),
        'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
        'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        'notes' => trim((string) ($_POST['notes'] ?? '')),
    ];

    $errors = [];
    if ($data['name'] === '') {
        $errors['name'] = 'Client name is required.';
    }
    if ($data['code'] === '') {
        $errors['code'] = 'Client code is required.';
    } elseif ($clientRepo->existsByCode($data['code'], $clientId)) {
        $errors['code'] = 'That client code is already in use.';
    }
    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        $errors['status'] = 'Invalid client status.';
    }
    if ($data['timezone'] === '') {
        $errors['timezone'] = 'Timezone is required.';
    }
    if ($data['contact_email'] !== '' && !is_valid_email($data['contact_email'])) {
        $errors['contact_email'] = 'Enter a valid contact email address.';
    }

    if ($errors !== []) {
        redirect_with_errors('clients/edit.php?id=' . $clientId, $errors, $_POST);
    }

    $clientRepo->update($clientId, $data);
    audit_log(auth_user_id(), $clientId, 'client_updated', 'client', (string) $clientId, 'Updated client ' . $data['name']);
    flash('success', 'Client updated successfully.');
    redirect('clients/index.php');
}

$formData = $_SESSION['_old_input'] ?? $client;

$pageTitle = 'Edit Client';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <h2>Edit Client</h2>
            <p>Update the tenant record and support contact details.</p>

            <form method="post" class="form-grid">
                <?= csrf_input() ?>
                <label>
                    <span>Client Name</span>
                    <input type="text" name="name" value="<?= e((string) ($formData['name'] ?? '')) ?>" required>
                    <?php if ($message = field_error($errors, 'name')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>Client Code</span>
                    <input type="text" name="code" value="<?= e((string) ($formData['code'] ?? '')) ?>" required>
                    <?php if ($message = field_error($errors, 'code')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= (($formData['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($formData['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
                <label>
                    <span>Timezone</span>
                    <input type="text" name="timezone" value="<?= e((string) ($formData['timezone'] ?? 'Australia/Sydney')) ?>" required>
                    <?php if ($message = field_error($errors, 'timezone')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>Contact Name</span>
                    <input type="text" name="contact_name" value="<?= e((string) ($formData['contact_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>Contact Email</span>
                    <input type="email" name="contact_email" value="<?= e((string) ($formData['contact_email'] ?? '')) ?>">
                    <?php if ($message = field_error($errors, 'contact_email')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label class="full-width">
                    <span>Contact Phone</span>
                    <input type="text" name="contact_phone" value="<?= e((string) ($formData['contact_phone'] ?? '')) ?>">
                </label>
                <label class="full-width">
                    <span>Notes</span>
                    <textarea name="notes" rows="5"><?= e((string) ($formData['notes'] ?? '')) ?></textarea>
                </label>
                <div class="full-width form-actions">
                    <button type="submit" class="button">Save Changes</button>
                    <a class="button button--secondary" href="<?= e(base_url('clients/index.php')) ?>">Back</a>
                </div>
            </form>
        </section>
    </main>
</div>
<?php consume_old_input(); ?>
<?php require APP_PATH . '/includes/footer.php'; ?>
