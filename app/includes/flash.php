<?php $flashMessages = get_flash_messages(); ?>
<?php if (!empty($flashMessages)): ?>
    <div class="flash-stack">
        <?php foreach ($flashMessages as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
