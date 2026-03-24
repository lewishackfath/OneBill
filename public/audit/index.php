<?php
declare(strict_types=1);
$title = 'Audit Logs';
require_once dirname(__DIR__, 2) . '/app/includes/header.php';
require_role(['super_admin', 'platform_admin', 'client_admin']);

$auditRepo = new AuditRepository();
$clientRepo = new ClientRepository();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$filters = [
    'action' => trim((string) ($_GET['action'] ?? '')),
    'user_id' => trim((string) ($_GET['user_id'] ?? '')),
    'client_id' => trim((string) ($_GET['client_id'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];

$rows = $auditRepo->search($filters, $perPage, $offset);
$total = $auditRepo->countSearch($filters);
$totalPages = max(1, (int) ceil($total / $perPage));

$userOptions = db()->query('SELECT id, email, first_name, last_name FROM users ORDER BY email ASC')->fetchAll();
$clientOptions = $clientRepo->findAccessibleForCurrentUser();
$actionOptions = $auditRepo->distinctActions();
?>
<div class="page-actions"><h2>Audit Log Viewer</h2></div>

<section class="card">
    <form method="get" class="filter-grid">
        <div class="form-row">
            <label for="action">Action</label>
            <select name="action" id="action">
                <option value="">All actions</option>
                <?php foreach ($actionOptions as $action): ?>
                    <option value="<?= e($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= e($action) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="user_id">User</label>
            <select name="user_id" id="user_id">
                <option value="">All users</option>
                <?php foreach ($userOptions as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (string) $filters['user_id'] === (string) $user['id'] ? 'selected' : '' ?>>
                        <?= e(trim($user['first_name'] . ' ' . $user['last_name']) . ' (' . $user['email'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="client_id">Client</label>
            <select name="client_id" id="client_id">
                <option value="">All clients</option>
                <?php foreach ($clientOptions as $client): ?>
                    <option value="<?= (int) $client['id'] ?>" <?= (string) $filters['client_id'] === (string) $client['id'] ? 'selected' : '' ?>>
                        <?= e($client['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="date_from">Date From</label>
            <input type="date" name="date_from" id="date_from" value="<?= e($filters['date_from']) ?>">
        </div>
        <div class="form-row">
            <label for="date_to">Date To</label>
            <input type="date" name="date_to" id="date_to" value="<?= e($filters['date_to']) ?>">
        </div>
        <div class="form-row button-row align-end">
            <button type="submit" class="button">Filter</button>
            <a class="button button-secondary" href="<?= e(base_url('/audit/index.php')) ?>">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>When</th><th>Action</th><th>User</th><th>Client</th><th>Entity</th><th>Description</th><th>IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="muted">No audit entries found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= e($row['created_at']) ?></td>
                        <td><span class="pill"><?= e($row['action']) ?></span></td>
                        <td><?= e($row['user_email'] ?? 'System') ?></td>
                        <td><?= e($row['client_name'] ?? '-') ?></td>
                        <td><?= e($row['entity_type'] . ($row['entity_id'] ? ' #' . $row['entity_id'] : '')) ?></td>
                        <td><?= e($row['description'] ?? '') ?></td>
                        <td><?= e($row['ip_address'] ?? '') ?></td>
                    </tr>
                    <?php if (!empty($row['metadata_json'])): ?>
                        <tr class="metadata-row"><td colspan="7"><pre><?= e($row['metadata_json']) ?></pre></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): $query = $_GET; $query['page'] = $i; ?>
                <a class="page-link <?= $i === $page ? 'active' : '' ?>" href="<?= e(base_url('/audit/index.php?' . http_build_query($query))) ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>
<?php require_once dirname(__DIR__, 2) . '/app/includes/footer.php'; ?>
