<?php $authUser = auth_user(); ?>
<header class="topbar">
    <div>
        <h1 class="topbar__title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <div class="topbar__actions">
        <?php if ($authUser): ?>
            <span class="topbar__user"><?= e($authUser['display_name'] ?? $authUser['email']) ?></span>
            <a class="button button--secondary" href="<?= e(base_url('logout.php')) ?>">Sign out</a>
        <?php endif; ?>
    </div>
</header>
