<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? app_config('name', '3CX CDR Processor');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/css/app.css')) ?>">
</head>
<body>
