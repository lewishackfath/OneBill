<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/UserRepository.php';

require_user_admin_access();

$authUser = auth_user();

$userRepo = new UserRepository();
$userId = (int) ($_GET['id'] ?? 0);
$user = $userRepo->findVisibleById($userId, $authUser);
if ($user === null) {
    http_response_code(404);
    exit('User not found.');
}

$pageTitle = 'View User';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>

        <section class="card section-card">
            <div class="page-actions">
                <div>
                    <h2><?= e(trim((string) $user['first_name'] . ' ' . (string) $user['last_name'])) ?></h2>
                    <p><?= e((string) $user['email']) ?></p>
                </div>
                <a class="button button--secondary" href="<?= e(base_url('users/edit.php?id=' . $userId)) ?>">Edit User</a>
            </div>

            <div class="meta-list">
                <div class="meta-row">
                    <div class="meta-row__label">Status</div>
                    <div><span class="badge <?= ((int) $user['is_active'] === 1) ? 'badge--success' : 'badge--muted' ?>"><?= ((int) $user['is_active'] === 1) ? 'Active' : 'Inactive' ?></span></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Role</div>
                    <div><?= e($userRepo->getPrimaryRoleNameForUser($userId)) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Last Login</div>
                    <div><?= e((string) ($user['last_login_at'] ?: 'Never')) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Created</div>
                    <div><?= e((string) $user['created_at']) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Updated</div>
                    <div><?= e((string) $user['updated_at']) ?></div>
                </div>
            </div>
        </section>

        <section class="card section-card">
            <h3>Client Assignments</h3>
            <?php if (($user['client_access'] ?? []) === []): ?>
                <div class="empty-state">This user has no client assignments.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Access Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user['client_access'] as $assignment): ?>
                                <tr>
                                    <td><?= e((string) $assignment['client_name']) ?></td>
                                    <td><?= e(ucfirst((string) $assignment['client_status'])) ?></td>
                                    <td><?= e(ucfirst((string) $assignment['access_level'])) ?></td>
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
