<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$currentPath = rtrim((string) $currentPath, '/');

$navItems = [
    ['label' => 'Dashboard', 'href' => base_url('dashboard.php'), 'match' => '/dashboard.php'],
    ['label' => 'Clients', 'href' => base_url('clients/index.php'), 'match' => '/clients'],
    ['label' => 'Users', 'href' => base_url('users/index.php'), 'match' => '/users'],
    ['label' => 'Roles', 'href' => base_url('roles/index.php'), 'match' => '/roles'],
    ['label' => 'My Profile', 'href' => base_url('profile/index.php'), 'match' => '/profile'],
    ['label' => 'Settings', 'href' => base_url('settings/index.php'), 'match' => '/settings'],
];
?>
<aside class="sidebar">
    <div class="sidebar__brand">
        <a href="<?= e(base_url('dashboard.php')) ?>"><?= e(app_config('name', '3CX CDR Processor')) ?></a>
    </div>
    <nav class="sidebar__nav">
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = str_contains($currentPath, $item['match']); ?>
            <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>
</aside>
