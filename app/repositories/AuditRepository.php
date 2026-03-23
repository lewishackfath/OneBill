<?php

declare(strict_types=1);

final class AuditRepository
{
    public function recent(int $limit = 10): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = db()->prepare('SELECT a.created_at, a.action, a.entity_type, a.description, u.first_name, u.last_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT ' . $limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
