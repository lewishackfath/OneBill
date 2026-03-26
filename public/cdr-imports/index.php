<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/CdrImportRunRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

require_cdr_import_access();

$authUser = auth_user();
$repo = new CdrImportRunRepository();
$clientRepo = new ClientRepository();

$filters = [
    'status' => trim((string) ($_GET['status'] ?? '')),
    'phone_system_id' => (int) ($_GET['phone_system_id'] ?? 0),
    'client_id' => (int) ($_GET['client_id'] ?? 0),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$total = $repo->countVisibleRunsForUser($authUser, $filters);
$runs = $repo->getVisibleRunsForUser($authUser, $filters, $page, $perPage);
$totalPages = max(1, (int) ceil($total / $perPage));
$phoneSystemOptions = $repo->getVisiblePhoneSystemOptionsForUser($authUser);
$clientOptions = $clientRepo->getOptionsForUser($authUser);

$pageTitle = 'CDR Imports';
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
                    <h2>CDR Import Runs</h2>
                    <p>Review passive socket collection sessions and the raw CDR records they staged for processing.</p>
                </div>
            </div>

            <form method="get" class="form-grid filter-form">
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        <?php foreach (['running', 'success', 'partial', 'failed'] as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>" <?= $filters['status'] === $statusOption ? 'selected' : '' ?>><?= e(ucfirst($statusOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Client</span>
                    <select name="client_id">
                        <option value="0">All clients</option>
                        <?php foreach ($clientOptions as $client): ?>
                            <option value="<?= (int) $client['id'] ?>" <?= $filters['client_id'] === (int) $client['id'] ? 'selected' : '' ?>><?= e((string) $client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Phone System</span>
                    <select name="phone_system_id">
                        <option value="0">All systems</option>
                        <?php foreach ($phoneSystemOptions as $system): ?>
                            <option value="<?= (int) $system['id'] ?>" <?= $filters['phone_system_id'] === (int) $system['id'] ? 'selected' : '' ?>>
                                <?= e((string) $system['client_name'] . ' — ' . $system['system_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Date From</span>
                    <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
                </label>
                <label>
                    <span>Date To</span>
                    <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
                </label>
                <label class="full-width">
                    <span>&nbsp;</span>
                    <div class="inline-list">
                        <button type="submit" class="button">Apply Filters</button>
                        <a class="button button--secondary" href="<?= e(base_url('cdr-imports/index.php')) ?>">Reset</a>
                    </div>
                </label>
            </form>

            <?php if ($runs === []): ?>
                <div class="empty-state">No CDR import runs match the current filters.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Started</th>
                                <th>Phone System</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Counts</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($runs as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?= e((string) $row['started_at']) ?></strong>
                                        <div class="table-subtext">Ended: <?= e((string) ($row['ended_at'] ?? '—')) ?></div>
                                        <div class="table-subtext">Type: <?= e((string) $row['run_type']) ?></div>
                                    </td>
                                    <td>
                                        <strong><?= e((string) $row['system_name']) ?></strong>
                                        <div class="table-subtext">Code: <?= e((string) $row['system_code']) ?></div>
                                        <div class="table-subtext"><?= e((string) $row['host']) ?>:<?= e((string) $row['port']) ?></div>
                                    </td>
                                    <td><?= e((string) $row['client_name']) ?></td>
                                    <td>
                                        <?php $statusClass = match ((string) $row['status']) {
                                            'success' => 'badge--success',
                                            'partial' => 'badge--warning',
                                            'failed' => 'badge--warning',
                                            default => 'badge--muted',
                                        }; ?>
                                        <span class="badge <?= $statusClass ?>"><?= e(ucfirst((string) $row['status'])) ?></span>
                                    </td>
                                    <td>
                                        <div>Received: <?= (int) $row['records_received'] ?></div>
                                        <div class="table-subtext">Inserted: <?= (int) $row['records_inserted'] ?></div>
                                        <div class="table-subtext">Skipped: <?= (int) $row['records_skipped'] ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['error_message'])): ?>
                                            <?= e((string) $row['error_message']) ?>
                                        <?php else: ?>
                                            <span class="table-subtext">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="inline-list" style="margin-top:16px;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php
                            $query = $_GET;
                            $query['page'] = $i;
                            $url = base_url('cdr-imports/index.php?' . http_build_query($query));
                            ?>
                            <a class="button <?= $i === $page ? '' : 'button--secondary' ?> button--small" href="<?= e($url) ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
