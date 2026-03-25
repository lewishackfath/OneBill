<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/UserRepository.php';
require_once APP_PATH . '/repositories/ClientRepository.php';
require_once APP_PATH . '/repositories/RoleRepository.php';
require_once APP_PATH . '/repositories/AuditRepository.php';

$authUser = auth_user();
$userRepo = new UserRepository();
$clientRepo = new ClientRepository();
$roleRepo = new RoleRepository();
$auditRepo = new AuditRepository();

$pageTitle = 'Dashboard';
$stats = [
    'users_total' => $userRepo->countVisibleForUser($authUser),
    'users_active' => $userRepo->countActiveVisibleForUser($authUser),
    'clients_total' => $clientRepo->countVisibleForUser($authUser),
    'clients_active' => $clientRepo->countActiveVisibleForUser($authUser),
    'roles_total' => $roleRepo->countAll(),
    'assigned_clients' => auth_assigned_client_count(),
];
$recentAudit = is_platform_user() ? $auditRepo->recent(8) : [];

require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="cards">
            <div class="card stat-card">
                <div class="stat-card__label">Visible Users</div>
                <div class="stat-card__value"><?= e((string) $stats['users_total']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Active Users</div>
                <div class="stat-card__value"><?= e((string) $stats['users_active']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Visible Clients</div>
                <div class="stat-card__value"><?= e((string) $stats['clients_total']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Assigned Clients</div>
                <div class="stat-card__value"><?= e((string) $stats['assigned_clients']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-card__label">Current Role</div>
                <div class="stat-card__value stat-card__value--small"><?= e(auth_primary_role_name()) ?></div>
            </div>
        </section>

        <section class="dashboard-grid">
            <section class="card section-card">
                <h2>Working Context</h2>
                <div class="meta-list">
                    <div class="meta-row">
                        <div class="meta-row__label">Signed in user</div>
                        <div><?= e($authUser['display_name'] ?? $authUser['email']) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-row__label">Current client</div>
                        <div><?= e((string) (current_client_name() ?? 'None selected')) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-row__label">Client access</div>
                        <div><?= e((string) (current_client_access_level() ?? '—')) ?></div>
                    </div>
                    <div class="meta-row">
                        <div class="meta-row__label">Client selector</div>
                        <div><?= e((string) (auth_assigned_client_count() > 0 ? 'Available in top bar' : 'No assigned clients')) ?></div>
                    </div>
                </div>
            </section>

            <section class="card section-card">
                <h2>Quick Links</h2>
                <div class="quick-links">
                    <?php if (can_view_users_nav()): ?>
                        <a class="quick-link" href="<?= e(base_url('users/index.php')) ?>">Manage Users</a>
                    <?php endif; ?>
                    <?php if (can_view_clients_nav()): ?>
                        <a class="quick-link" href="<?= e(base_url('clients/index.php')) ?>">Manage Clients</a>
                    <?php endif; ?>
                    <a class="quick-link" href="<?= e(base_url('profile/index.php')) ?>">My Profile</a>
                    <a class="quick-link" href="<?= e(base_url('profile/password.php')) ?>">Change Password</a>
                </div>
            </section>
        </section>

        <section class="card section-card">
            <div class="page-actions">
                <div>
                    <h2>Upcoming Modules</h2>
                    <p>The shell is now tenancy-aware and ready for the next phase.</p>
                </div>
            </div>
            <div class="future-cards">
                <div class="future-card">
                    <h3>Phone Systems</h3>
                    <p>Per-client 3CX connection records and credential handling.</p>
                    <span class="badge badge--warning">Next phase</span>
                </div>
                <div class="future-card">
                    <h3>CDR Imports</h3>
                    <p>Raw call record ingestion, staging, and processing jobs.</p>
                    <span class="badge badge--warning">Next phase</span>
                </div>
                <div class="future-card">
                    <h3>Billing Runs</h3>
                    <p>Usage processing, rating rules, and invoice preparation.</p>
                    <span class="badge badge--warning">Planned</span>
                </div>
                <div class="future-card">
                    <h3>ConnectWise</h3>
                    <p>Agreement mapping and invoice line generation.</p>
                    <span class="badge badge--warning">Planned</span>
                </div>
            </div>
        </section>

        <?php if (is_platform_user()): ?>
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
        <?php endif; ?>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
