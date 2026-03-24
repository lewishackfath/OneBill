<?php
declare(strict_types=1);
$title = 'Dashboard';
require_once dirname(__DIR__) . '/app/includes/header.php';

$userRepo = new UserRepository();
$clientRepo = new ClientRepository();
$auditRepo = new AuditRepository();

$totalUsers = $userRepo->countVisibleToCurrentUser();
$totalClients = $clientRepo->countVisibleToCurrentUser();
$actions = $auditRepo->distinctActions();
?>
<section class="grid cards-4">
    <div class="card stat-card"><div class="stat-label">Visible Users</div><div class="stat-value"><?= (int) $totalUsers ?></div></div>
    <div class="card stat-card"><div class="stat-label">Visible Clients</div><div class="stat-value"><?= (int) $totalClients ?></div></div>
    <div class="card stat-card"><div class="stat-label">Current Client</div><div class="stat-value"><?= e(current_client_name() ?? 'None') ?></div></div>
    <div class="card stat-card"><div class="stat-label">Role</div><div class="stat-value"><?= e(implode(', ', auth_user()['role_keys'] ?? [])) ?></div></div>
</section>

<section class="grid cards-2">
    <div class="card">
        <h2>Quick Actions</h2>
        <div class="button-row">
            <?php if (user_has_role(['super_admin', 'platform_admin'])): ?>
                <a class="button" href="<?= e(base_url('/clients/index.php')) ?>">Manage Clients</a>
            <?php endif; ?>
            <?php if (user_has_role(['super_admin', 'platform_admin', 'client_admin'])): ?>
                <a class="button" href="<?= e(base_url('/users/index.php')) ?>">Manage Users</a>
                <a class="button button-secondary" href="<?= e(base_url('/audit/index.php')) ?>">View Audit Logs</a>
            <?php endif; ?>
            <?php if (user_has_role(['super_admin', 'platform_admin'])): ?>
                <a class="button button-secondary" href="<?= e(base_url('/settings/index.php')) ?>">Settings</a>
            <?php endif; ?>
            <a class="button button-secondary" href="<?= e(base_url('/profile/index.php')) ?>">My Profile</a>
        </div>
    </div>
    <div class="card">
        <h2>Upcoming Modules</h2>
        <div class="placeholder-list">
            <div class="placeholder-item">Phone Systems</div>
            <div class="placeholder-item">CDR Imports</div>
            <div class="placeholder-item">Billing Runs</div>
            <div class="placeholder-item">ConnectWise Agreements</div>
        </div>
    </div>
</section>

<section class="card">
    <h2>Known Audit Actions</h2>
    <?php if (empty($actions)): ?>
        <p class="muted">No audit activity has been recorded yet.</p>
    <?php else: ?>
        <div class="pill-list">
            <?php foreach ($actions as $action): ?><span class="pill"><?= e($action) ?></span><?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once dirname(__DIR__) . '/app/includes/footer.php'; ?>
