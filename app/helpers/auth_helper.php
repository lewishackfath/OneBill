<?php
declare(strict_types=1);

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'roles' => $user['roles'] ?? [],
        'role_keys' => $user['role_keys'] ?? [],
    ];
    $_SESSION['_created_at'] = time();
    $_SESSION['_last_activity_at'] = time();
    unset($_SESSION['user_client_ids']);
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

function user_full_name(?array $user = null): string
{
    $user ??= auth_user();
    if (!$user) {
        return '';
    }
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
}

function user_has_role(string|array $roleKeys): bool
{
    $roleKeys = (array) $roleKeys;
    $owned = auth_user()['role_keys'] ?? [];
    return count(array_intersect($roleKeys, $owned)) > 0;
}

function is_platform_user(): bool
{
    return user_has_role(['super_admin', 'platform_admin']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please sign in to continue.');
        redirect('/login.php');
    }
}

function require_role(string|array $roleKeys): void
{
    require_login();
    if (!user_has_role($roleKeys)) {
        http_response_code(403);
        require public_path('/403.php');
        exit;
    }
}

function public_path(string $relative): string
{
    return dirname(__DIR__, 2) . '/public' . $relative;
}
