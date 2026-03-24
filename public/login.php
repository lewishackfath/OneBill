<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';

if (is_logged_in()) {
    redirect('/dashboard.php');
}

$error = null;

if (is_post()) {
    validate_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $success = false;
    if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
        $fullUser = (new UserRepository())->findWithRoleAndClients((int) $user['id']);
        login_user($fullUser);
        ensure_current_client_context();
        db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([(int) $user['id']]);
        db()->prepare('INSERT INTO login_attempts (email, ip_address, user_agent, was_successful) VALUES (?, ?, ?, 1)')
            ->execute([$email, request_ip(), substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
        audit_log((int) $user['id'], current_client_id(), 'auth.login', 'user', (string) $user['id'], 'User signed in');
        flash('success', 'Welcome back, ' . trim($user['first_name'] . ' ' . $user['last_name']) . '.');
        redirect('/dashboard.php');
    }

    db()->prepare('INSERT INTO login_attempts (email, ip_address, user_agent, was_successful) VALUES (?, ?, ?, 0)')
        ->execute([$email, request_ip(), substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)]);
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - <?= e(app_setting('application_name', app_config('name', '3CX CDR Processor'))) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/app.css')) ?>">
</head>
<body class="login-body">
<main class="login-wrap">
    <div class="login-card">
        <h1><?= e(app_setting('application_name', app_config('name', '3CX CDR Processor'))) ?></h1>
        <p class="muted">Sign in to continue.</p>
        <?php require dirname(__DIR__) . '/app/includes/flash.php'; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_input() ?>
            <div class="form-row">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required autocomplete="username">
            </div>
            <div class="form-row">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required autocomplete="current-password">
            </div>
            <button type="submit">Sign in</button>
        </form>
    </div>
</main>
</body>
</html>
