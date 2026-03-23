<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';

$pageTitle = 'Clients';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <section class="card section-card">
            <h2>Clients</h2>
            <p>This module is the next build step.</p>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
