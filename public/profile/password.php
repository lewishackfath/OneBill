<?php
declare(strict_types=1);
$title = 'Change Password';
require_once dirname(__DIR__, 2) . '/app/includes/header.php';

if (is_post()) {
    validate_csrf();

    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([(int) auth_user()['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($currentPassword, $hash)) {
        flash('danger', 'Your current password is incorrect.');
        redirect('/profile/password.php');
    }
    if ($newPassword !== $confirmPassword) {
        flash('danger', 'New password confirmation does not match.');
        redirect('/profile/password.php');
    }
    if (strlen($newPassword) < 12) {
        flash('danger', 'New password must be at least 12 characters.');
        redirect('/profile/password.php');
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
        ->execute([$newHash, (int) auth_user()['id']]);

    audit_log((int) auth_user()['id'], current_client_id(), 'user.change_password', 'user', (string) auth_user()['id'], 'User changed their password');
    flash('success', 'Password updated successfully.');
    redirect('/profile/password.php');
}
?>
<section class="card narrow">
    <h2>Change Password</h2>
    <p class="muted"><?= e((string) app_setting('password_policy_text', 'Minimum 12 characters recommended.')) ?></p>
    <form method="post">
        <?= csrf_input() ?>
        <div class="form-row">
            <label for="current_password">Current Password</label>
            <input type="password" name="current_password" id="current_password" required>
        </div>
        <div class="form-row">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" required>
        </div>
        <div class="form-row">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <div class="button-row">
            <button type="submit" class="button">Update Password</button>
        </div>
    </form>
</section>
<?php require_once dirname(__DIR__, 2) . '/app/includes/footer.php'; ?>
