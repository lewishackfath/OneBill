<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap/init.php';
require_once APP_PATH . '/services/AuthService.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $authService = new AuthService();
        if ($authService->attemptLogin($email, $password)) {
            flash('success', 'Welcome back.');
            redirect('dashboard.php');
        }
        $error = 'Invalid login details.';
    }
}

$pageTitle = 'Sign In';
require APP_PATH . '/includes/header.php';
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>3CX CDR Processor</h1>
        <p class="auth-subtitle">Sign in to continue</p>

        <?php require APP_PATH . '/includes/flash.php'; ?>

        <?php if ($error !== null): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="form-stack">
            <?= csrf_input() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="button">Sign in</button>
        </form>
    </div>
</div>
<?php require APP_PATH . '/includes/footer.php'; ?>
