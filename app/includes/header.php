<?php
require_once dirname(__DIR__) . '/bootstrap/init.php';
require_login();
ensure_current_client_context();
$title = $title ?? app_config('name', '3CX CDR Processor');
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= e(asset_url('/assets/css/app.css')) ?>">
</head>
<body>
<div class="app-layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <?php require __DIR__ . '/topbar.php'; ?>
        <main class="page-content">
            <?php require __DIR__ . '/flash.php'; ?>
