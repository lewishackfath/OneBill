<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/repositories/RoleRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

require_user_admin_access();

$authUser = auth_user();

$userRepo = new UserRepository();
$roleRepo = new RoleRepository();
$clientRepo = new ClientRepository();
$userId = (int) ($_GET['id'] ?? 0);
$user = $userRepo->findVisibleById($userId, $authUser);
if ($user === null) {
    http_response_code(404);
    exit('User not found.');
}

$assignableRoles = $roleRepo->getAssignableRolesForUser($authUser);
$availableClients = $clientRepo->getOptionsForUser($authUser);
$errors = validation_errors();

if (submitted('POST')) {
    verify_csrf();

    $roleKey = (string) ($_POST['role_key'] ?? '');
    $selectedClientIds = array_map('intval', posted_array('client_ids'));
    $accessLevels = posted_array('access_levels');
    $password = (string) ($_POST['password'] ?? '');

    $data = [
        'first_name' => trim((string) ($_POST['first_name'] ?? '')),
        'last_name' => trim((string) ($_POST['last_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'is_active' => (string) ($_POST['is_active'] ?? '1') === '1' ? 1 : 0,
    ];

    $allowedRoleKeys = array_column($assignableRoles, 'role_key');
    $allowedClientIds = array_column($availableClients, 'id');

    $errors = [];
    if ($data['first_name'] === '') { $errors['first_name'] = 'First name is required.'; }
    if ($data['last_name'] === '') { $errors['last_name'] = 'Last name is required.'; }
    if ($data['email'] === '') {
        $errors['email'] = 'Email is required.';
    } elseif (!is_valid_email($data['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif ($userRepo->emailExists($data['email'], $userId)) {
        $errors['email'] = 'That email address is already in use.';
    }
    if ($password !== '' && mb_strlen($password) < 12) {
        $errors['password'] = 'Use a password with at least 12 characters.';
    }
    if ($roleKey === '' || !in_array($roleKey, $allowedRoleKeys, true)) {
        $errors['role_key'] = 'Select a valid role.';
    }
    if ($selectedClientIds === []) {
        $errors['client_ids'] = 'Select at least one client assignment.';
    }

    $assignments = [];
    foreach ($selectedClientIds as $clientId) {
        if (!in_array($clientId, $allowedClientIds, true)) {
            $errors['client_ids'] = 'One or more selected clients are not permitted.';
            continue;
        }
        $level = (string) ($accessLevels[$clientId] ?? 'standard');
        if (!in_array($level, ['admin', 'standard', 'readonly'], true)) {
            $level = 'standard';
        }
        $assignments[] = [
            'client_id' => $clientId,
            'access_level' => $level,
        ];
    }

    if ($errors !== []) {
        redirect_with_errors('users/edit.php?id=' . $userId, $errors, $_POST);
    }

    $userRepo->update($userId, $data);
    if ($password !== '') {
        $userRepo->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
    }
    $userRepo->syncSingleRole($userId, $roleKey);
    $userRepo->syncClientAccess($userId, $assignments);

    audit_log(auth_user_id(), null, 'user_updated', 'user', (string) $userId, 'Updated user ' . $data['email'], [
        'role_key' => $roleKey,
        'client_ids' => array_column($assignments, 'client_id'),
        'password_reset' => $password !== '',
    ]);
    if ((int) auth_user_id() === $userId) {
        refresh_authenticated_user_session();
        initialise_current_client_context();
    }

    flash('success', 'User updated successfully.');
    redirect('users/index.php');
}

$formData = $_SESSION['_old_input'] ?? [
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'email' => $user['email'],
    'is_active' => (string) $user['is_active'],
    'role_key' => $user['roles'][0] ?? '',
    'client_ids' => array_map(static fn(array $row): string => (string) $row['client_id'], $user['client_access']),
    'access_levels' => array_column($user['client_access'], 'access_level', 'client_id'),
];

$pageTitle = 'Edit User';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <h2>Edit User</h2>
            <p>Update account details, role assignment, client access, and optional password reset.</p>

            <form method="post" class="form-stack">
                <?= csrf_input() ?>

                <div class="form-section">
                    <h3>User Details</h3>
                    <div class="form-grid">
                        <label>
                            <span>First Name</span>
                            <input type="text" name="first_name" value="<?= e((string) ($formData['first_name'] ?? '')) ?>" required>
                            <?php if ($message = field_error($errors, 'first_name')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Last Name</span>
                            <input type="text" name="last_name" value="<?= e((string) ($formData['last_name'] ?? '')) ?>" required>
                            <?php if ($message = field_error($errors, 'last_name')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="email" value="<?= e((string) ($formData['email'] ?? '')) ?>" required>
                            <?php if ($message = field_error($errors, 'email')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                        <label>
                            <span>Status</span>
                            <select name="is_active">
                                <option value="1" <?= (($formData['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= (($formData['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Reset Password</h3>
                    <div class="form-grid">
                        <label class="full-width">
                            <span>New Password</span>
                            <input type="password" name="password" placeholder="Leave blank to keep current password">
                            <?php if ($message = field_error($errors, 'password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Role</h3>
                    <div class="form-grid">
                        <label class="full-width">
                            <span>Role</span>
                            <select name="role_key" required>
                                <option value="">Select a role</option>
                                <?php foreach ($assignableRoles as $role): ?>
                                    <option value="<?= e((string) $role['role_key']) ?>" <?= (($formData['role_key'] ?? '') === $role['role_key']) ? 'selected' : '' ?>>
                                        <?= e((string) $role['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($message = field_error($errors, 'role_key')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                        </label>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Client Access</h3>
                    <?php if ($message = field_error($errors, 'client_ids')): ?><div class="alert alert--error"><?= e($message) ?></div><?php endif; ?>
                    <div class="assignment-list">
                        <?php $selectedIds = array_map('intval', (array) ($formData['client_ids'] ?? [])); ?>
                        <?php $accessMap = (array) ($formData['access_levels'] ?? []); ?>
                        <?php foreach ($availableClients as $client): ?>
                            <?php $clientId = (int) $client['id']; ?>
                            <div class="assignment-item">
                                <input type="checkbox" name="client_ids[]" value="<?= $clientId ?>" <?= in_array($clientId, $selectedIds, true) ? 'checked' : '' ?>>
                                <div>
                                    <strong><?= e((string) $client['name']) ?></strong><br>
                                    <span class="topbar__user"><?= e((string) $client['status']) ?></span>
                                </div>
                                <select name="access_levels[<?= $clientId ?>]">
                                    <option value="admin" <?= (($accessMap[$clientId] ?? 'standard') === 'admin') ? 'selected' : '' ?>>Admin</option>
                                    <option value="standard" <?= (($accessMap[$clientId] ?? 'standard') === 'standard') ? 'selected' : '' ?>>Standard</option>
                                    <option value="readonly" <?= (($accessMap[$clientId] ?? 'standard') === 'readonly') ? 'selected' : '' ?>>Read only</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button">Save Changes</button>
                    <a class="button button--secondary" href="<?= e(base_url('users/index.php')) ?>">Back</a>
                </div>
            </form>
        </section>
    </main>
</div>
<?php consume_old_input(); ?>
<?php require APP_PATH . '/includes/footer.php'; ?>
