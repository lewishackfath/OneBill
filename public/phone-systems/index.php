<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/PhoneSystemRepository.php';

require_phone_system_admin_access();

$authUser = auth_user();
$repo = new PhoneSystemRepository();
$phoneSystems = $repo->getAllVisibleForUser($authUser);

$pageTitle = 'Phone Systems';
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
                    <h2>3CX Phone Systems</h2>
                    <p>Manage per-client 3CX systems configured for passive socket CDR collection.</p>
                </div>
                <a class="button" href="<?= e(base_url('phone-systems/create.php')) ?>">Add Phone System</a>
            </div>

            <?php if ($phoneSystems === []): ?>
                <div class="empty-state">No phone systems have been configured yet.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>System</th>
                                <th>Client</th>
                                <th>Socket</th>
                                <th>CDR</th>
                                <th>Status</th>
                                <th>Last Connect</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($phoneSystems as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= e((string) $row['system_name']) ?></strong>
                                        <div class="table-subtext">Code: <?= e((string) $row['system_code']) ?></div>
                                        <div class="table-subtext">Timezone: <?= e((string) $row['timezone']) ?></div>
                                        <?php if (!empty($row['base_url'])): ?>
                                            <div class="table-subtext">URL: <?= e((string) $row['base_url']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string) $row['client_name']) ?></td>
                                    <td>
                                        <?php if (!empty($row['host']) && !empty($row['port'])): ?>
                                            <strong><?= e((string) $row['host']) ?>:<?= e((string) $row['port']) ?></strong>
                                            <div class="table-subtext">Mode: <?= e((string) ($row['connection_mode'] ?? 'passive_socket')) ?></div>
                                            <div class="table-subtext">Timeout: <?= e((string) ($row['socket_timeout_seconds'] ?? 10)) ?>s</div>
                                        <?php else: ?>
                                            <span class="table-subtext">Not configured</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= !empty($row['cdr_enabled']) ? 'badge--success' : 'badge--muted' ?>">
                                            <?= !empty($row['cdr_enabled']) ? 'Enabled' : 'Disabled' ?>
                                        </span>
                                        <div class="table-subtext">Profile: <?= e((string) ($row['cdr_field_profile'] ?? 'default')) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($row['status'] === 'active') ? 'badge--success' : 'badge--muted' ?>">
                                            <?= e(ucfirst((string) $row['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['last_connect_at'])): ?>
                                            <?= e((string) $row['last_connect_at']) ?><br>
                                            <span class="badge <?= (($row['last_connect_status'] ?? '') === 'success') ? 'badge--success' : 'badge--warning' ?>">
                                                <?= e(ucfirst((string) ($row['last_connect_status'] ?? 'unknown'))) ?>
                                            </span>
                                            <?php if (!empty($row['last_connect_message'])): ?>
                                                <div class="table-subtext"><?= e((string) $row['last_connect_message']) ?></div>
                                            <?php endif; ?>
                                        <?php elseif (!empty($row['last_tested_at'])): ?>
                                            <?= e((string) $row['last_tested_at']) ?><br>
                                            <span class="badge <?= (($row['last_test_status'] ?? '') === 'success') ? 'badge--success' : 'badge--warning' ?>">
                                                <?= e(ucfirst((string) ($row['last_test_status'] ?? 'unknown'))) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="table-subtext">Not tested yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="inline-list">
                                            <a class="button button--secondary button--small" href="<?= e(base_url('phone-systems/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
                                            <form method="post" action="<?= e(base_url('phone-systems/test-connection.php')) ?>">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button type="submit" class="button button--secondary button--small">Test Socket</button>
                                            </form>
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
