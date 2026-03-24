<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap/init.php';
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/app.css')) ?>">
</head>
<body class="app-shell">
<main class="container narrow">
    <div class="card">
        <h1>403 — Forbidden</h1>
        <p>You do not have permission to access that page.</p>
        <div class="button-row">
            <a class="button" href="<?= e(base_url('/dashboard.php')) ?>">Back to Dashboard</a>
        </div>
    </div>
</main>
</body>
</html>
