<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentPath = rtrim((string) $currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'href' => base_url('dashboard.php'), 'match' => '/dashboard.php', 'show' => true],
    ['label' => 'Clients', 'href' => base_url('clients/index.php'), 'match' => '/clients', 'show' => can_view_clients_nav()],
    ['label' => 'Users', 'href' => base_url('users/index.php'), 'match' => '/users', 'show' => can_view_users_nav()],
    ['label' => 'Roles', 'href' => base_url('roles/index.php'), 'match' => '/roles', 'show' => can_access_roles_page()],
    ['label' => 'Audit Logs', 'href' => base_url('audit/index.php'), 'match' => '/audit', 'show' => can_access_audit_page()],
    ['label' => 'Settings', 'href' => base_url('settings/index.php'), 'match' => '/settings', 'show' => can_access_settings_page()],
    ['label' => 'My Profile', 'href' => base_url('profile/index.php'), 'match' => '/profile', 'show' => true],
];
?>
<aside class="sidebar">
    <div class="sidebar__brand">
        <a href="<?= e(base_url('dashboard.php')) ?>"><?= e(app_config('name', '3CX CDR Processor')) ?></a>
    </div>

    <?php if (is_logged_in()): ?>
        <div class="sidebar__context">
            <div class="sidebar__context-label">Signed in as</div>
            <div class="sidebar__context-value"><?= e(auth_user()['display_name'] ?? auth_user()['email'] ?? '') ?></div>
            <div class="sidebar__context-meta"><?= e(auth_primary_role_name()) ?></div>
            <?php if (current_client_name() !== null): ?>
                <div class="sidebar__context-client">Client: <?= e((string) current_client_name()) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <nav class="sidebar__nav">
        <?php foreach ($navItems as $item): ?>
            <?php if (!$item['show']) { continue; } ?>
            <?php $isActive = str_contains($currentPath, $item['match']); ?>
            <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>
</aside>
