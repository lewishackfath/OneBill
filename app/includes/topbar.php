<?php
$clientRepo = new ClientRepository();
$availableClients = $clientRepo->findAccessibleForCurrentUser();
?>
<header class="topbar">
    <div class="topbar-left">
        <h1 class="page-title"><?= e($title ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar-right">
        <?php if (!empty($availableClients)): ?>
            <form method="post" action="<?= e(base_url('/switch-client.php')) ?>" class="inline-form">
                <?= csrf_input() ?>
                <label for="current_client_id" class="sr-only">Current client</label>
                <select name="current_client_id" id="current_client_id" onchange="this.form.submit()">
                    <?php foreach ($availableClients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>" <?= ((int) $client['id'] === (int) current_client_id()) ? 'selected' : '' ?>>
                            <?= e($client['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <div class="topbar-meta">
            <span class="muted"><?= e(user_full_name()) ?></span>
            <?php if (current_client_name()): ?>
                <span class="pill"><?= e(current_client_name()) ?></span>
            <?php endif; ?>
        </div>
    </div>
</header>
