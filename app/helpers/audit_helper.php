<?php

declare(strict_types=1);

function audit_log(?int $userId, ?int $clientId, string $action, string $entityType, ?string $entityId = null, ?string $description = null, ?array $metadata = null): void
{
    try {
        $stmt = db()->prepare('INSERT INTO audit_logs (user_id, client_id, action, entity_type, entity_id, description, metadata_json, ip_address) VALUES (:user_id, :client_id, :action, :entity_type, :entity_id, :description, :metadata_json, :ip_address)');
        $stmt->execute([
            ':user_id' => $userId,
            ':client_id' => $clientId,
            ':action' => $action,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':description' => $description,
            ':metadata_json' => $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('audit_log failed: ' . $e->getMessage());
    }
}
