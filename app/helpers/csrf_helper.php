<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function validate_csrf(): void
{
    $token = (string) ($_POST['_csrf_token'] ?? '');
    if (!hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('CSRF token validation failed.');
    }
}
