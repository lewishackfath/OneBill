<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';

$user = auth_user();
$pageTitle = 'My Profile';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <section class="card section-card">
            <h2>Account Details</h2>
            <p><strong>Name:</strong> <?= e((string) ($user['display_name'] ?? '')) ?></p>
            <p><strong>Email:</strong> <?= e((string) ($user['email'] ?? '')) ?></p>
            <p><strong>Roles:</strong> <?= e(implode(', ', $user['roles'] ?? [])) ?></p>
            <p><a class="button" href="<?= e(base_url('profile/password.php')) ?>">Change Password</a></p>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
