<?php

declare(strict_types=1);

final class AuditRepository
{
    public function recent(int $limit = 10, ?array $authUser = null): array
    {
        $limit = max(1, min($limit, 100));
        $authUser ??= auth_user();
        [$whereSql, $params] = $this->buildVisibilityWhere($authUser);

        $sql = "SELECT a.*,
                       u.first_name, u.last_name, u.email,
                       c.name AS client_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN clients c ON c.id = a.client_id
                {$whereSql}
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT {$limit}";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countVisibleForUser(array $authUser, array $filters = []): int
    {
        [$whereSql, $params] = $this->buildFilteredWhere($authUser, $filters);
        $stmt = db()->prepare("SELECT COUNT(*) FROM audit_logs a {$whereSql}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function searchVisibleForUser(array $authUser, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilteredWhere($authUser, $filters);
        $sql = "SELECT a.*,
                       u.first_name, u.last_name, u.email,
                       c.name AS client_name
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN clients c ON c.id = a.client_id
                {$whereSql}
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function actionOptionsVisibleForUser(array $authUser): array
    {
        [$whereSql, $params] = $this->buildVisibilityWhere($authUser);
        $stmt = db()->prepare("SELECT DISTINCT a.action FROM audit_logs a {$whereSql} ORDER BY a.action ASC");
        $stmt->execute($params);
        return array_values(array_filter(array_map(static fn($value): string => (string) $value, $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }

    private function buildFilteredWhere(array $authUser, array $filters): array
    {
        [$baseWhereSql, $params] = $this->buildVisibilityWhere($authUser);
        $clauses = [];

        if ($baseWhereSql !== '') {
            $clauses[] = preg_replace('/^\s*WHERE\s+/i', '', $baseWhereSql);
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $clauses[] = 'a.created_at >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $clauses[] = 'a.created_at <= :date_to';
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $action = trim((string) ($filters['action'] ?? ''));
        if ($action !== '') {
            $clauses[] = 'a.action = :action';
            $params[':action'] = $action;
        }

        $clientId = (int) ($filters['client_id'] ?? 0);
        if ($clientId > 0) {
            $clauses[] = 'a.client_id = :client_id';
            $params[':client_id'] = $clientId;
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $clauses[] = 'a.user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $clauses[] = '(a.description LIKE :search OR a.entity_type LIKE :search OR a.entity_id LIKE :search OR a.action LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        $whereSql = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);
        return [$whereSql, $params];
    }

    private function buildVisibilityWhere(?array $authUser): array
    {
        if (!is_array($authUser) || $authUser === []) {
            return ['WHERE 1 = 0', []];
        }

        $roles = $authUser['roles'] ?? [];
        if (in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true)) {
            return ['', []];
        }

        $clientIds = array_values(array_filter(array_map('intval', $authUser['accessible_client_ids'] ?? [])));
        if ($clientIds === []) {
            return ['WHERE 1 = 0', []];
        }

        $placeholders = [];
        $params = [];
        foreach ($clientIds as $index => $clientId) {
            $key = ':visible_client_' . $index;
            $placeholders[] = $key;
            $params[$key] = $clientId;
        }

        return ['WHERE a.client_id IN (' . implode(', ', $placeholders) . ')', $params];
    }
}
