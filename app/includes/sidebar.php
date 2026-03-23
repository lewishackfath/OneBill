<aside class="sidebar">
    <div class="sidebar__brand">
        <a href="<?= e(base_url('dashboard.php')) ?>"><?= e(app_config('name', '3CX CDR Processor')) ?></a>
    </div>
    <nav class="sidebar__nav">
        <a href="<?= e(base_url('dashboard.php')) ?>">Dashboard</a>
        <a href="<?= e(base_url('clients/index.php')) ?>">Clients</a>
        <a href="<?= e(base_url('users/index.php')) ?>">Users</a>
        <a href="<?= e(base_url('roles/index.php')) ?>">Roles</a>
        <a href="<?= e(base_url('profile/index.php')) ?>">My Profile</a>
        <a href="<?= e(base_url('settings/index.php')) ?>">Settings</a>
    </nav>
</aside>
