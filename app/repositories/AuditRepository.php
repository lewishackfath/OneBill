<?php
declare(strict_types=1);

class AuditRepository
{
    public function create(array $data): void
    {
        $stmt = db()->prepare('
            INSERT INTO audit_logs (user_id, client_id, action, entity_type, entity_id, description, metadata_json, ip_address)
            VALUES (:user_id, :client_id, :action, :entity_type, :entity_id, :description, :metadata_json, :ip_address)
        ');
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':client_id' => $data['client_id'],
            ':action' => $data['action'],
            ':entity_type' => $data['entity_type'],
            ':entity_id' => $data['entity_id'],
            ':description' => $data['description'],
            ':metadata_json' => $data['metadata_json'],
            ':ip_address' => $data['ip_address'],
        ]);
    }

    public function search(array $filters, int $limit, int $offset): array
    {
        [$whereSql, $params] = $this->buildWhere($filters);
        $sql = "
            SELECT al.*, u.email AS user_email, c.name AS client_name
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.user_id
            LEFT JOIN clients c ON c.id = al.client_id
            $whereSql
            ORDER BY al.created_at DESC, al.id DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = db()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSearch(array $filters): int
    {
        [$whereSql, $params] = $this->buildWhere($filters);
        $stmt = db()->prepare("SELECT COUNT(*) FROM audit_logs al $whereSql");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function buildWhere(array $filters): array
    {
        $clauses = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $clauses[] = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $clauses[] = 'al.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['client_id'])) {
            $clauses[] = 'al.client_id = :client_id';
            $params[':client_id'] = (int) $filters['client_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'al.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'al.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!user_has_role(['super_admin', 'platform_admin'])) {
            $clientIds = available_client_ids_for_user();
            if (empty($clientIds)) {
                $clauses[] = '1=0';
            } else {
                $ph = [];
                foreach ($clientIds as $i => $clientId) {
                    $k = ':client_scope_' . $i;
                    $ph[] = $k;
                    $params[$k] = (int) $clientId;
                }
                $clauses[] = '(al.client_id IN (' . implode(',', $ph) . ') OR al.client_id IS NULL)';
            }
        }

        return ['WHERE ' . implode(' AND ', $clauses), $params];
    }

    public function distinctActions(): array
    {
        $stmt = db()->query('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');
        return array_values(array_filter(array_column($stmt->fetchAll(), 'action')));
    }
}
