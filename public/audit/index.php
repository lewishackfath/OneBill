<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/middleware/require_role.php';
require_once APP_PATH . '/repositories/AuditRepository.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';

require_audit_access();

$authUser = auth_user();
$auditRepo = new AuditRepository();
$userRepo = new UserRepository();
$clientRepo = new ClientRepository();

$filters = [
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
    'action' => trim((string) ($_GET['action'] ?? '')),
    'client_id' => (int) ($_GET['client_id'] ?? 0),
    'user_id' => (int) ($_GET['user_id'] ?? 0),
    'search' => trim((string) ($_GET['search'] ?? '')),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$totalRows = $auditRepo->countVisibleForUser($authUser, $filters);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$rows = $auditRepo->searchVisibleForUser($authUser, $filters, $page, $perPage);
$actionOptions = $auditRepo->actionOptionsVisibleForUser($authUser);
$userOptions = $userRepo->listVisibleForUser($authUser);
$clientOptions = $clientRepo->getAllVisibleForUser($authUser);

function audit_filter_url(array $filters, int $page): string
{
    $query = array_filter([
        'date_from' => $filters['date_from'] ?? '',
        'date_to' => $filters['date_to'] ?? '',
        'action' => $filters['action'] ?? '',
        'client_id' => (int) ($filters['client_id'] ?? 0) ?: null,
        'user_id' => (int) ($filters['user_id'] ?? 0) ?: null,
        'search' => $filters['search'] ?? '',
        'page' => $page,
    ], static fn($value): bool => $value !== null && $value !== '');

    return base_url('audit/index.php' . ($query !== [] ? '?' . http_build_query($query) : ''));
}

$pageTitle = 'Audit Logs';
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
                    <h2>Audit Log Viewer</h2>
                    <p>Review important platform and client administration events.</p>
                </div>
            </div>

            <form method="get" class="form-stack filter-form">
                <div class="form-grid">
                    <label>
                        <span>Date From</span>
                        <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
                    </label>
                    <label>
                        <span>Date To</span>
                        <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
                    </label>
                    <label>
                        <span>Action</span>
                        <select name="action">
                            <option value="">All actions</option>
                            <?php foreach ($actionOptions as $action): ?>
                                <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
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
                        <span>User</span>
                        <select name="user_id">
                            <option value="0">All users</option>
                            <?php foreach ($userOptions as $user): ?>
                                <option value="<?= (int) $user['id'] ?>" <?= $filters['user_id'] === (int) $user['id'] ? 'selected' : '' ?>><?= e(trim(((string) $user['first_name']) . ' ' . ((string) $user['last_name'])) ?: (string) $user['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="full-width">
                        <span>Search</span>
                        <input type="text" name="search" value="<?= e($filters['search']) ?>" placeholder="Description, action, entity type or entity id">
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="button">Apply Filters</button>
                    <a class="button button--secondary" href="<?= e(base_url('audit/index.php')) ?>">Clear</a>
                </div>
            </form>
        </section>

        <section class="card section-card">
            <div class="page-actions page-actions--tight">
                <div>
                    <h2>Entries</h2>
                    <p><?= e((string) $totalRows) ?> matching record<?= $totalRows === 1 ? '' : 's' ?>.</p>
                </div>
            </div>

            <?php if ($rows === []): ?>
                <div class="empty-state">No audit records matched the selected filters.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Client</th>
                                <th>User</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= e((string) $row['created_at']) ?></td>
                                    <td><span class="badge"><?= e((string) $row['action']) ?></span></td>
                                    <td>
                                        <?= e((string) $row['entity_type']) ?>
                                        <?php if (!empty($row['entity_id'])): ?>
                                            <div class="table-subtext">ID: <?= e((string) $row['entity_id']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e((string) ($row['client_name'] ?? '—')) ?></td>
                                    <td>
                                        <?php $displayUser = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? ''))); ?>
                                        <?= e($displayUser !== '' ? $displayUser : ((string) ($row['email'] ?? 'System'))) ?>
                                    </td>
                                    <td>
                                        <?= e((string) ($row['description'] ?? '')) ?>
                                        <?php if (!empty($row['metadata_json'])): ?>
                                            <details class="metadata-details">
                                                <summary>Metadata</summary>
                                                <pre><?= e((string) $row['metadata_json']) ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination">
                        <?php if ($page > 1): ?>
                            <a class="button button--secondary button--small" href="<?= e(audit_filter_url($filters, $page - 1)) ?>">Previous</a>
                        <?php endif; ?>
                        <span class="pagination__status">Page <?= e((string) $page) ?> of <?= e((string) $totalPages) ?></span>
                        <?php if ($page < $totalPages): ?>
                            <a class="button button--secondary button--small" href="<?= e(audit_filter_url($filters, $page + 1)) ?>">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
