<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';
require_once APP_PATH . '/repositories/RoleRepository.php';
require_once APP_PATH . '/repositories/AuditRepository.php';

$userRepo = new UserRepository();
$clientRepo = new ClientRepository();
$roleRepo = new RoleRepository();
$auditRepo = new AuditRepository();

$pageTitle = 'Dashboard';
$stats = [
    'users_total' => $userRepo->countAll(),
    'users_active' => $userRepo->countActive(),
    'clients_total' => $clientRepo->countAll(),
    'clients_active' => $clientRepo->countActive(),
    'roles_total' => $roleRepo->countAll(),
];
$recentAudit = $auditRepo->recent(8);

require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="cards">
            <div class="card stat-card">
                <div class="stat-card__label">Total Users</div>
                <div class="stat-card__value"><?= e((string) $stats['users_total']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Active Users</div>
                <div class="stat-card__value"><?= e((string) $stats['users_active']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Total Clients</div>
                <div class="stat-card__value"><?= e((string) $stats['clients_total']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Active Clients</div>
                <div class="stat-card__value"><?= e((string) $stats['clients_active']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Roles</div>
                <div class="stat-card__value"><?= e((string) $stats['roles_total']) ?></div>
            </div>
        </section>

        <section class="card section-card">
            <h2>Phase 1 Status</h2>
            <p>Your core shell is now in place. Next up is building the client and user management modules on top of this foundation.</p>
        </section>

        <section class="card section-card">
            <h2>Recent Audit Activity</h2>
            <?php if ($recentAudit === []): ?>
                <p>No audit entries yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Description</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAudit as $row): ?>
                                <tr>
                                    <td><?= e((string) $row['created_at']) ?></td>
                                    <td><?= e((string) $row['action']) ?></td>
                                    <td><?= e((string) $row['entity_type']) ?></td>
                                    <td><?= e((string) ($row['description'] ?? '')) ?></td>
                                    <td><?= e(trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')))) ?></td>
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
