<?php

declare(strict_types=1);

if (!defined('APP_BOOTSTRAPPED')) {
    require_once dirname(__DIR__) . '/app/bootstrap/init.php';
}

$pageTitle = 'Access Denied';
require APP_PATH . '/includes/header.php';
?>
<div class="auth-page">
    <section class="auth-card auth-card--wide">
        <h1>Access denied</h1>
        <p class="auth-subtitle">Your account does not have permission to access this page.</p>
        <div class="form-actions">
            <a class="button" href="<?= e(base_url('dashboard.php')) ?>">Return to Dashboard</a>
            <a class="button button--secondary" href="<?= e(base_url('profile/index.php')) ?>">My Profile</a>
        </div>
    </section>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
