<?php
declare(strict_types=1);

function base_url(string $path = ''): string
{
    $base = (string) app_config('url', '');
    return $base . $path;
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function asset_url(string $path): string
{
    return base_url($path);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
