<?php

declare(strict_types=1);

function auth_user(): ?array
{
    if (empty($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        return null;
    }

    return $_SESSION['auth_user'];
}

function auth_user_id(): ?int
{
    $user = auth_user();
    return isset($user['id']) ? (int) $user['id'] : null;
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
        'roles' => array_values($user['roles'] ?? []),
        'primary_role' => (string) ($user['primary_role'] ?? ($user['roles'][0] ?? '')),
        'primary_role_name' => (string) ($user['primary_role_name'] ?? ''),
        'client_access' => array_values($user['client_access'] ?? []),
        'accessible_client_ids' => array_values($user['accessible_client_ids'] ?? []),
        'accessible_clients' => array_values($user['accessible_clients'] ?? []),
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function refresh_authenticated_user_session(): void
{
    $sessionUser = auth_user();
    if ($sessionUser === null) {
        return;
    }

    $stmt = db()->prepare('SELECT id, email, first_name, last_name, is_active FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $sessionUser['id']]);
    $user = $stmt->fetch();

    if (!$user || (int) $user['is_active'] !== 1) {
        logout_user();
        return;
    }

    $roleStmt = db()->prepare(
        "SELECT r.role_key, r.role_name
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = :user_id
         ORDER BY FIELD(r.role_key, 'super_admin','platform_admin','client_admin','client_user','readonly'), r.role_name"
    );
    $roleStmt->execute([':user_id' => (int) $user['id']]);
    $roleRows = $roleStmt->fetchAll();

    $roles = array_map(static fn(array $row): string => (string) $row['role_key'], $roleRows);
    $primaryRole = $roles[0] ?? '';
    $primaryRoleName = (string) ($roleRows[0]['role_name'] ?? '');

    $clientStmt = db()->prepare(
        "SELECT uca.client_id, uca.access_level, c.name AS client_name, c.status AS client_status
         FROM user_client_access uca
         INNER JOIN clients c ON c.id = uca.client_id
         WHERE uca.user_id = :user_id
         ORDER BY c.name ASC"
    );
    $clientStmt->execute([':user_id' => (int) $user['id']]);
    $clientAccess = $clientStmt->fetchAll();

    if (in_array($primaryRole, ['super_admin', 'platform_admin'], true)) {
        $allClients = db()->query("SELECT id, name, status FROM clients ORDER BY name ASC")->fetchAll();
        $accessibleClients = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'status' => (string) $row['status'],
                'access_level' => 'platform',
            ];
        }, $allClients);
    } else {
        $accessibleClients = array_map(static function (array $row): array {
            return [
                'id' => (int) $row['client_id'],
                'name' => (string) $row['client_name'],
                'status' => (string) $row['client_status'],
                'access_level' => (string) $row['access_level'],
            ];
        }, $clientAccess);
    }

    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'email' => (string) $user['email'],
        'first_name' => (string) $user['first_name'],
        'last_name' => (string) $user['last_name'],
        'display_name' => trim(((string) $user['first_name']) . ' ' . ((string) $user['last_name'])),
        'roles' => $roles,
        'primary_role' => $primaryRole,
        'primary_role_name' => $primaryRoleName,
        'client_access' => $clientAccess,
        'accessible_client_ids' => array_map(static fn(array $row): int => (int) $row['id'], $accessibleClients),
        'accessible_clients' => $accessibleClients,
    ];
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

function auth_primary_role(): string
{
    return (string) (auth_user()['primary_role'] ?? '');
}

function auth_primary_role_name(): string
{
    return (string) (auth_user()['primary_role_name'] ?? auth_primary_role());
}

function is_super_admin(): bool
{
    return user_has_role('super_admin');
}

function is_platform_admin(): bool
{
    return user_has_role('platform_admin');
}

function is_platform_user(): bool
{
    return user_has_role(['super_admin', 'platform_admin']);
}

function is_client_admin(): bool
{
    return user_has_role('client_admin');
}

function can_manage_users(): bool
{
    return user_has_role(['super_admin', 'platform_admin', 'client_admin']);
}

function can_manage_clients(): bool
{
    return is_platform_user();
}

function can_access_roles_page(): bool
{
    return is_platform_user();
}

function can_access_settings_page(): bool
{
    return is_platform_user();
}

function can_view_clients_nav(): bool
{
    return is_platform_user();
}

function can_view_users_nav(): bool
{
    return can_manage_users();
}

function auth_accessible_client_ids(): array
{
    $user = auth_user();
    return array_values(array_map('intval', $user['accessible_client_ids'] ?? []));
}

function auth_accessible_clients(): array
{
    $user = auth_user();
    $clients = $user['accessible_clients'] ?? [];
    return is_array($clients) ? array_values($clients) : [];
}

function auth_assigned_client_count(): int
{
    return count(auth_accessible_client_ids());
}

function auth_has_client_access(int $clientId): bool
{
    return in_array($clientId, auth_accessible_client_ids(), true);
}
