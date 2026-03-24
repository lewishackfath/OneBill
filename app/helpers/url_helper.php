<?php

declare(strict_types=1);

function base_url(string $path = ''): string
{
    $base = rtrim((string) app_config('url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function redirect(string $path): never
{
    $location = preg_match('#^https?://#i', $path) ? $path : base_url($path);
    header('Location: ' . $location);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
