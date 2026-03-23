<?php

declare(strict_types=1);

function old(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function posted_array(string $key): array
{
    $value = $_POST[$key] ?? [];
    return is_array($value) ? $value : [];
}

function normalise_email(string $email): string
{
    return mb_strtolower(trim($email));
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function redirect_with_errors(string $path, array $errors, array $old = []): never
{
    $_SESSION['_form_errors'] = $errors;
    $_SESSION['_old_input'] = $old;
    redirect($path);
}

function validation_errors(): array
{
    $errors = $_SESSION['_form_errors'] ?? [];
    unset($_SESSION['_form_errors']);
    return is_array($errors) ? $errors : [];
}

function old_input(string $key, mixed $default = ''): mixed
{
    $old = $_SESSION['_old_input'] ?? [];
    return $old[$key] ?? $default;
}

function consume_old_input(): void
{
    unset($_SESSION['_old_input']);
}

function field_error(array $errors, string $field): ?string
{
    return isset($errors[$field]) ? (string) $errors[$field] : null;
}

function submitted(string $method = 'POST'): bool
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === strtoupper($method);
}
