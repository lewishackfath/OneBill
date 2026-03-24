<?php
declare(strict_types=1);
$title = 'My Profile';
require_once dirname(__DIR__, 2) . '/app/includes/header.php';
$user = (new UserRepository())->findWithRoleAndClients((int) auth_user()['id']);
?>
<section class="card">
    <h2>Account Details</h2>
    <dl class="details-grid">
        <dt>Name</dt><dd><?= e(trim($user['first_name'] . ' ' . $user['last_name'])) ?></dd>
        <dt>Email</dt><dd><?= e($user['email']) ?></dd>
        <dt>Roles</dt><dd><?= e(implode(', ', array_column($user['roles'], 'role_name'))) ?></dd>
        <dt>Status</dt><dd><?= ((int) $user['is_active'] === 1) ? 'Active' : 'Inactive' ?></dd>
        <dt>Last Login</dt><dd><?= e($user['last_login_at'] ?? 'Never') ?></dd>
    </dl>
    <h3>Client Access</h3>
    <?php if (empty($user['client_access']) && !is_platform_user()): ?>
        <p class="muted">No client access is assigned.</p>
    <?php else: ?>
        <ul class="simple-list">
            <?php foreach ($user['client_access'] as $client): ?>
                <li><?= e($client['client_name']) ?> — <?= e($client['access_level']) ?></li>
            <?php endforeach; ?>
            <?php if (is_platform_user()): ?><li>Platform-wide visibility</li><?php endif; ?>
        </ul>
    <?php endif; ?>
    <div class="button-row">
        <a class="button" href="<?= e(base_url('/profile/password.php')) ?>">Change Password</a>
    </div>
</section>
<?php require_once dirname(__DIR__, 2) . '/app/includes/footer.php'; ?>
