<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

$authUser = auth_user();
$clientRepo = new ClientRepository();
$clients = $clientRepo->getAllVisibleForUser($authUser);
$canManageClients = $clientRepo->canManageClients($authUser);

$pageTitle = 'Clients';
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
                    <h2>Client Directory</h2>
                    <p>Manage tenant records and core contact details.</p>
                </div>
                <?php if ($canManageClients): ?>
                    <a class="button" href="<?= e(base_url('clients/create.php')) ?>">Create Client</a>
                <?php endif; ?>
            </div>

            <?php if ($clients === []): ?>
                <div class="empty-state">No clients are available for your account yet.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Timezone</th>
                                <th>Contact</th>
                                <th>Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= e((string) $client['name']) ?></td>
                                    <td><?= e((string) $client['code']) ?></td>
                                    <td>
                                        <span class="badge <?= ($client['status'] === 'active') ? 'badge--success' : 'badge--muted' ?>">
                                            <?= e(ucfirst((string) $client['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= e((string) $client['timezone']) ?></td>
                                    <td>
                                        <?= e((string) ($client['contact_name'] ?: '—')) ?><br>
                                        <span class="topbar__user"><?= e((string) ($client['contact_email'] ?: '')) ?></span>
                                    </td>
                                    <td><?= e((string) $client['updated_at']) ?></td>
                                    <td>
                                        <?php if ($canManageClients): ?>
                                            <a class="button button--secondary button--small" href="<?= e(base_url('clients/edit.php?id=' . (int) $client['id'])) ?>">Edit</a>
                                        <?php else: ?>
                                            <span class="topbar__user">View only</span>
                                        <?php endif; ?>
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
