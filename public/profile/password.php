<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/bootstrap/init.php';
require_once APP_PATH . '/middleware/require_login.php';
require_once APP_PATH . '/repositories/UserRepository.php';

$userId = auth_user_id();
$userRepo = new UserRepository();
$currentUser = $userRepo->findById((int) $userId);
if ($currentUser === null) {
    http_response_code(404);
    exit('User not found.');
}

$errors = validation_errors();

if (submitted('POST')) {
    verify_csrf();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $errors = [];
    if ($currentPassword === '' || !password_verify($currentPassword, (string) $currentUser['password_hash'])) {
        $errors['current_password'] = 'Your current password is incorrect.';
    }
    if (mb_strlen($newPassword) < 12) {
        $errors['new_password'] = 'Use a new password with at least 12 characters.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors['confirm_password'] = 'Password confirmation does not match.';
    }

    if ($errors !== []) {
        redirect_with_errors('profile/password.php', $errors, []);
    }

    $userRepo->updatePassword((int) $userId, password_hash($newPassword, PASSWORD_DEFAULT));
    audit_log((int) $userId, null, 'password_changed', 'user', (string) $userId, 'User changed their own password');
    flash('success', 'Password changed successfully.');
    redirect('profile/index.php');
}

$pageTitle = 'Change Password';
require APP_PATH . '/includes/header.php';
?>
<div class="layout">
    <?php require APP_PATH . '/includes/sidebar.php'; ?>
    <main class="main">
        <?php require APP_PATH . '/includes/topbar.php'; ?>
        <?php require APP_PATH . '/includes/flash.php'; ?>

        <section class="card section-card">
            <h2>Change Password</h2>
            <p>Update your own account password.</p>

            <form method="post" class="form-stack">
                <?= csrf_input() ?>
                <label>
                    <span>Current Password</span>
                    <input type="password" name="current_password" required>
                    <?php if ($message = field_error($errors, 'current_password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>New Password</span>
                    <input type="password" name="new_password" required>
                    <?php if ($message = field_error($errors, 'new_password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <label>
                    <span>Confirm New Password</span>
                    <input type="password" name="confirm_password" required>
                    <?php if ($message = field_error($errors, 'confirm_password')): ?><span class="field-error"><?= e($message) ?></span><?php endif; ?>
                </label>
                <div class="form-actions">
                    <button type="submit" class="button">Change Password</button>
                    <a class="button button--secondary" href="<?= e(base_url('profile/index.php')) ?>">Back</a>
                </div>
            </form>
        </section>
    </main>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
