<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';

$expired = !empty($_SESSION['_session_expired']);
unset($_SESSION['_session_expired']);

$pageTitle = 'Session Expired';
require APP_PATH . '/includes/header.php';
?>
<div class="auth-page">
    <section class="auth-card auth-card--wide">
        <h1>Session expired</h1>
        <p class="auth-subtitle"><?= e($expired ? 'Your session timed out due to inactivity. Please sign in again.' : 'Please sign in to continue.') ?></p>
        <div class="form-actions">
            <a class="button" href="<?= e(base_url('login.php')) ?>">Go to Login</a>
        </div>
    </section>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
