<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/UserRepository.php';

$userRepo = new UserRepository();
$user = $userRepo->findById((int) auth_user_id());
$pageTitle = 'My Profile';
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
                    <h2>Account Details</h2>
                    <p>Your authenticated account details.</p>
                </div>
                <a class="button" href="<?= e(base_url('profile/password.php')) ?>">Change Password</a>
            </div>

            <div class="meta-list">
                <div class="meta-row">
                    <div class="meta-row__label">Name</div>
                    <div><?= e(trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''))) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Email</div>
                    <div><?= e((string) ($user['email'] ?? '')) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Roles</div>
                    <div><?= e(implode(', ', $user['roles'] ?? [])) ?></div>
                </div>
                <div class="meta-row">
                    <div class="meta-row__label">Last Login</div>
                    <div><?= e((string) (($user['last_login_at'] ?? '') ?: 'Never')) ?></div>
                </div>
            </div>
        </section>

        <section class="card section-card">
            <h3>Client Access</h3>
            <?php if (($user['client_access'] ?? []) === []): ?>
                <div class="empty-state">No client assignments recorded.</div>
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
