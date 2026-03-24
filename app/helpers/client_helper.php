<?php
declare(strict_types=1);

function available_client_ids_for_user(?int $userId = null): array
{
    $user = $userId ? (new UserRepository())->findWithRoleAndClients($userId) : auth_user();
    if (!$user) {
        return [];
    }

    if (($user['role_keys'] ?? null) && array_intersect($user['role_keys'], ['super_admin', 'platform_admin'])) {
        return array_map('intval', array_column((new ClientRepository())->allActive(), 'id'));
    }

    if ($userId) {
        return array_map('intval', array_column($user['client_access'] ?? [], 'client_id'));
    }

    if (!isset($_SESSION['user_client_ids'])) {
        $repoUser = (new UserRepository())->findWithRoleAndClients((int) $user['id']);
        $_SESSION['user_client_ids'] = array_map('intval', array_column($repoUser['client_access'] ?? [], 'client_id'));
    }

    return $_SESSION['user_client_ids'];
}

function set_current_client_context(?int $clientId): void
{
    $allowed = available_client_ids_for_user();
    if ($clientId === null || !in_array($clientId, $allowed, true)) {
        unset($_SESSION['current_client_id'], $_SESSION['current_client_name']);
        return;
    }

    $client = (new ClientRepository())->findById($clientId);
    if (!$client) {
        unset($_SESSION['current_client_id'], $_SESSION['current_client_name']);
        return;
    }

    $_SESSION['current_client_id'] = (int) $client['id'];
    $_SESSION['current_client_name'] = $client['name'];
}

function ensure_current_client_context(): void
{
    if (!is_logged_in()) {
        return;
    }

    $allowed = available_client_ids_for_user();
    if (empty($allowed)) {
        unset($_SESSION['current_client_id'], $_SESSION['current_client_name']);
        return;
    }

    $current = $_SESSION['current_client_id'] ?? null;
    if ($current && in_array((int) $current, $allowed, true)) {
        return;
    }

    set_current_client_context((int) $allowed[0]);
}

function current_client_id(): ?int
{
    ensure_current_client_context();
    return isset($_SESSION['current_client_id']) ? (int) $_SESSION['current_client_id'] : null;
}

function current_client_name(): ?string
{
    ensure_current_client_context();
    return $_SESSION['current_client_name'] ?? null;
}

function can_access_client(int $clientId): bool
{
    return in_array($clientId, available_client_ids_for_user(), true);
}
