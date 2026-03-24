<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';
$reason = (string) ($_GET['reason'] ?? 'idle');
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/app.css')) ?>">
</head>
<body class="app-shell">
<main class="container narrow">
    <div class="card">
        <h1>Session Expired</h1>
        <p>Your session ended due to <?= e($reason === 'absolute' ? 'maximum session lifetime' : 'inactivity') ?>.</p>
        <div class="button-row">
            <a class="button" href="<?= e(base_url('/login.php')) ?>">Sign In Again</a>
        </div>
    </div>
</main>
</body>
</html>
