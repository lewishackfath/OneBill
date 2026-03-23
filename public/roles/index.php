<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';

$stmt = db()->query('SELECT role_key, role_name, description FROM roles ORDER BY role_name ASC');
$roles = $stmt->fetchAll();

$pageTitle = 'Roles';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <section class="card section-card">
            <h2>Roles</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Role Key</th><th>Name</th><th>Description</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= e((string) $role['role_key']) ?></td>
                                <td><?= e((string) $role['role_name']) ?></td>
                                <td><?= e((string) ($role['description'] ?? '')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
