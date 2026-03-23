<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/UserRepository.php';

require_user_admin_access();

$authUser = auth_user();
$userRepo = new UserRepository();
$users = $userRepo->listVisibleForUser($authUser);

$pageTitle = 'Users';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <div class="page-actions">
                <div>
                    <h2>User Management</h2>
                    <p>Create and manage application users, role assignment, and client access.</p>
                </div>
                <a class="button" href="<?= e(base_url('users/create.php')) ?>">Create User</a>
            </div>

            <?php if ($users === []): ?>
                <div class="empty-state">No users are visible for your access scope.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Primary Role</th>
                                <th>Clients</th>
                                <th>Last Login</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= e(trim((string) $user['first_name'] . ' ' . (string) $user['last_name'])) ?></td>
                                    <td><?= e((string) $user['email']) ?></td>
                                    <td>
                                        <span class="badge <?= ((int) $user['is_active'] === 1) ? 'badge--success' : 'badge--muted' ?>">
                                            <?= ((int) $user['is_active'] === 1) ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= e((string) $user['primary_role_name']) ?></td>
                                    <td><?= e((string) $user['client_count']) ?></td>
                                    <td><?= e((string) ($user['last_login_at'] ?: 'Never')) ?></td>
                                    <td>
                                        <div class="inline-list">
                                            <a class="button button--secondary button--small" href="<?= e(base_url('users/view.php?id=' . (int) $user['id'])) ?>">View</a>
                                            <a class="button button--secondary button--small" href="<?= e(base_url('users/edit.php?id=' . (int) $user['id'])) ?>">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
