<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';

require_settings_access();

$pageTitle = 'Settings';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>

        <section class="card section-card">
            <h2>Settings</h2>
            <p>This area is reserved for platform-wide configuration and Phase 1 hardening work.</p>
            <div class="empty-state">
                Settings groundwork is ready. The next milestone can add app settings, audit browsing, and security policy controls.
            </div>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
