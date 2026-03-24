<?php if ($message = flash('success')): ?>
    <div class="alert alert--success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert alert--error"><?= e($message) ?></div>
<?php endif; ?>
