<?php

declare(strict_types=1);

function auth_user(): ?array
{
    if (empty($_SESSION['auth_user'])) {
        return null;
    }

    return $_SESSION['auth_user'];
}

function auth_user_id(): ?int
{
    $user = auth_user();
    return $user['id'] ?? null;
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'first_name' => (string) $user['first_name'],
        'last_name' => (string) $user['last_name'],
        'display_name' => trim(((string) $user['first_name']) . ' ' . ((string) $user['last_name'])),
        'roles' => $user['roles'] ?? [],
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

function user_has_role(string|array $roles): bool
{
    $user = auth_user();
    if ($user === null) {
        return false;
    }

    $currentRoles = $user['roles'] ?? [];
    foreach ((array) $roles as $role) {
        if (in_array($role, $currentRoles, true)) {
            return true;
        }
    }

    return false;
}
