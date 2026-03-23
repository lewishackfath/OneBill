<?php

declare(strict_types=1);

function current_client_id(): ?int
{
    $value = $_SESSION['current_client_id'] ?? null;
    return is_numeric($value) ? (int) $value : null;
}

function set_current_client_id(?int $clientId): void
{
    $_SESSION['current_client_id'] = $clientId;
}
