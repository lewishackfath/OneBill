<?php
$authUser = auth_user();
$clientOptions = auth_accessible_clients();
$currentClient = current_client();
?>
<header class="topbar">
    <div>
        <h1 class="topbar__title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <?php if ($notice = current_client_required_notice()): ?>
            <div class="topbar__notice"><?= e($notice) ?></div>
        <?php endif; ?>
    </div>

    <div class="topbar__actions">
        <?php if ($authUser): ?>
            <?php if ($clientOptions !== []): ?>
                <form class="client-switcher" method="post" action="<?= e(base_url('switch-client.php')) ?>">
                    <?= csrf_input() ?>
                    <label>
                        <span>Current client</span>
                        <select name="current_client_id" onchange="this.form.submit()">
                            <?php foreach ($clientOptions as $client): ?>
                                <option value="<?= (int) $client['id'] ?>" <?= ((int) ($currentClient['id'] ?? 0) === (int) $client['id']) ? 'selected' : '' ?>>
                                    <?= e((string) $client['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI'] ?? base_url('dashboard.php')) ?>">
                </form>
            <?php endif; ?>

            <span class="badge"><?= e(auth_primary_role_name()) ?></span>
            <?php if ($currentClient !== null): ?>
                <span class="badge badge--muted"><?= e((string) $currentClient['name']) ?></span>
            <?php endif; ?>
            <span class="topbar__user"><?= e($authUser['display_name'] ?? $authUser['email']) ?></span>
            <a class="button button--secondary" href="<?= e(base_url('logout.php')) ?>">Sign out</a>
        <?php endif; ?>
    </div>
</header>
