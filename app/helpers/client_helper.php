<?php

declare(strict_types=1);

function current_client_id(): ?int
{
    $value = $_SESSION['current_client_id'] ?? null;
    return is_numeric($value) ? (int) $value : null;
}

function set_current_client_id(?int $clientId): void
{
    if ($clientId === null) {
        unset($_SESSION['current_client_id']);
        return;
    }

    $_SESSION['current_client_id'] = $clientId;
}

function current_client(): ?array
{
    $clientId = current_client_id();
    if ($clientId === null) {
        return null;
    }

    foreach (auth_accessible_clients() as $client) {
        if ((int) ($client['id'] ?? 0) === $clientId) {
            return $client;
        }
    }

    return null;
}

function current_client_name(): ?string
{
    $client = current_client();
    return $client['name'] ?? null;
}

function current_client_access_level(): ?string
{
    $client = current_client();
    return $client['access_level'] ?? null;
}

function can_access_client(int $clientId): bool
{
    if (is_platform_user()) {
        return true;
    }

    return auth_has_client_access($clientId);
}

function initialise_current_client_context(): void
{
    if (!is_logged_in()) {
        set_current_client_id(null);
        return;
    }

    $accessibleClients = auth_accessible_clients();
    if ($accessibleClients === []) {
        set_current_client_id(null);
        return;
    }

    $current = current_client_id();
    $validIds = array_map(static fn(array $client): int => (int) $client['id'], $accessibleClients);

    if ($current !== null && in_array($current, $validIds, true)) {
        return;
    }

    set_current_client_id((int) $accessibleClients[0]['id']);
}

function current_client_required_notice(): ?string
{
    if (current_client_id() !== null) {
        return null;
    }

    if (auth_assigned_client_count() === 0 && !is_platform_user()) {
        return 'Your account does not currently have any client assignments.';
    }

    if (auth_assigned_client_count() > 1 || is_platform_user()) {
        return 'Select a client from the top bar to set your working context.';
    }

    return null;
}
