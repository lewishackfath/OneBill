<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';

$message = 'Password change will be implemented in the next milestone.';
$pageTitle = 'Change Password';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <section class="card section-card">
            <h2>Change Password</h2>
            <p><?= e($message) ?></p>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
