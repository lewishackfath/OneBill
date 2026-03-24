<?php
declare(strict_types=1);

function request_ip(): ?string
{
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

function audit_log(?int $userId, ?int $clientId, string $action, string $entityType, ?string $entityId = null, ?string $description = null, array $metadata = []): void
{
    try {
        (new AuditRepository())->create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'metadata_json' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => request_ip(),
        ]);
    } catch (Throwable $e) {
    }
}
