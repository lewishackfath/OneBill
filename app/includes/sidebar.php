<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentPath = rtrim((string) $currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'href' => base_url('dashboard.php'), 'match' => '/dashboard.php', 'show' => true],
    ['label' => 'Clients', 'href' => base_url('clients/index.php'), 'match' => '/clients', 'show' => can_view_clients_nav()],
    ['label' => 'Phone Systems', 'href' => base_url('phone-systems/index.php'), 'match' => '/phone-systems', 'show' => can_view_phone_systems_nav()],
    ['label' => 'CDR Imports', 'href' => base_url('cdr-imports/index.php'), 'match' => '/cdr-imports', 'show' => can_view_cdr_imports_nav()],
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
            <?php
            $hrefPath = rtrim(parse_url((string) $item['href'], PHP_URL_PATH) ?: '', '/');
            $isActive = $currentPath === $hrefPath || ($item['match'] !== '' && str_contains($currentPath, (string) $item['match']));
            ?>
            <a href="<?= e((string) $item['href']) ?>" class="<?= $isActive ? 'is-active' : '' ?>">
                <?= e((string) $item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
