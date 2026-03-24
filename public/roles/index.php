<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/RoleRepository.php';

require_platform_admin_or_higher();

$roleRepo = new RoleRepository();
$roles = $roleRepo->getAll();

$pageTitle = 'Roles';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>

        <section class="card section-card">
            <h2>Roles</h2>
            <p>Current platform roles available for assignment.</p>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Role Key</th>
                            <th>Role Name</th>
                            <th>Description</th>
                        </tr>
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
