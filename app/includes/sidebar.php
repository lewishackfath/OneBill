<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <a href="<?= e(base_url('/dashboard.php')) ?>"><?= e(app_setting('application_name', app_config('name', '3CX CDR Processor'))) ?></a>
    </div>

    <nav class="sidebar-nav">
        <a class="<?= str_contains($currentPath, '/dashboard.php') ? 'active' : '' ?>" href="<?= e(base_url('/dashboard.php')) ?>">Dashboard</a>

        <?php if (user_has_role(['super_admin', 'platform_admin'])): ?>
            <a class="<?= str_contains($currentPath, '/clients/') ? 'active' : '' ?>" href="<?= e(base_url('/clients/index.php')) ?>">Clients</a>
        <?php endif; ?>

        <?php if (user_has_role(['super_admin', 'platform_admin', 'client_admin'])): ?>
            <a class="<?= str_contains($currentPath, '/users/') ? 'active' : '' ?>" href="<?= e(base_url('/users/index.php')) ?>">Users</a>
        <?php endif; ?>

        <?php if (user_has_role(['super_admin', 'platform_admin'])): ?>
            <a class="<?= str_contains($currentPath, '/roles/') ? 'active' : '' ?>" href="<?= e(base_url('/roles/index.php')) ?>">Roles</a>
            <a class="<?= str_contains($currentPath, '/audit/') ? 'active' : '' ?>" href="<?= e(base_url('/audit/index.php')) ?>">Audit Logs</a>
            <a class="<?= str_contains($currentPath, '/settings/') ? 'active' : '' ?>" href="<?= e(base_url('/settings/index.php')) ?>">Settings</a>
        <?php elseif (user_has_role('client_admin')): ?>
            <a class="<?= str_contains($currentPath, '/audit/') ? 'active' : '' ?>" href="<?= e(base_url('/audit/index.php')) ?>">Audit Logs</a>
        <?php endif; ?>

        <a class="<?= str_contains($currentPath, '/profile/') ? 'active' : '' ?>" href="<?= e(base_url('/profile/index.php')) ?>">My Profile</a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= e(user_full_name()) ?></div>
            <div class="sidebar-user-email"><?= e(auth_user()['email'] ?? '') ?></div>
        </div>
        <a class="button button-secondary button-block" href="<?= e(base_url('/logout.php')) ?>">Sign out</a>
    </div>
</aside>
