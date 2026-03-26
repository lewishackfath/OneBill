<?php

declare(strict_types=1);

final class CdrImportRunRepository
{
    public function startRun(int $phoneSystemId, string $runType = 'socket_session'): int
    {
        $stmt = db()->prepare(
            'INSERT INTO cdr_import_runs (phone_system_id, run_type, started_at, status, created_at)
             VALUES (:phone_system_id, :run_type, NOW(), :status, NOW())'
        );
        $stmt->execute([
            ':phone_system_id' => $phoneSystemId,
            ':run_type' => $runType,
            ':status' => 'running',
        ]);

        return (int) db()->lastInsertId();
    }

    public function finishRun(int $runId, string $status, int $received, int $inserted, int $skipped, ?string $errorMessage = null): void
    {
        $stmt = db()->prepare(
            'UPDATE cdr_import_runs
             SET ended_at = NOW(),
                 status = :status,
                 records_received = :records_received,
                 records_inserted = :records_inserted,
                 records_skipped = :records_skipped,
                 error_message = :error_message
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $runId,
            ':status' => $status,
            ':records_received' => $received,
            ':records_inserted' => $inserted,
            ':records_skipped' => $skipped,
            ':error_message' => $errorMessage,
        ]);
    }

    public function insertRawRecord(int $phoneSystemId, int $clientId, int $runId, string $rawLine): bool
    {
        $trimmed = trim($rawLine);
        if ($trimmed === '') {
            return false;
        }

        $hash = hash('sha256', $phoneSystemId . '|' . $trimmed);
        $stmt = db()->prepare(
            'INSERT INTO cdr_raw_records (
                phone_system_id, client_id, import_run_id, record_hash, raw_line,
                source_received_at, process_status, created_at
             ) VALUES (
                :phone_system_id, :client_id, :import_run_id, :record_hash, :raw_line,
                NOW(), :process_status, NOW()
             )'
        );

        try {
            $stmt->execute([
                ':phone_system_id' => $phoneSystemId,
                ':client_id' => $clientId,
                ':import_run_id' => $runId,
                ':record_hash' => $hash,
                ':raw_line' => $trimmed,
                ':process_status' => 'pending',
            ]);
            return true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    public function logListenerEvent(?int $phoneSystemId, ?int $clientId, string $eventType, string $severity, string $message, ?array $context = null): void
    {
        $stmt = db()->prepare(
            'INSERT INTO cdr_listener_events (
                phone_system_id, client_id, event_type, severity, message, context_json, created_at
             ) VALUES (
                :phone_system_id, :client_id, :event_type, :severity, :message, :context_json, NOW()
             )'
        );
        $stmt->execute([
            ':phone_system_id' => $phoneSystemId,
            ':client_id' => $clientId,
            ':event_type' => $eventType,
            ':severity' => $severity,
            ':message' => $message,
            ':context_json' => $context !== null ? json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    public function getVisibleRunsForUser(array $authUser, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));
        $offset = ($page - 1) * $perPage;
        [$whereSql, $params] = $this->buildFilteredWhere($authUser, $filters);

        $sql = "SELECT cir.*, ps.system_name, ps.system_code, ps.host, ps.port, c.name AS client_name
                FROM cdr_import_runs cir
                INNER JOIN phone_systems ps ON ps.id = cir.phone_system_id
                INNER JOIN clients c ON c.id = ps.client_id
                {$whereSql}
                ORDER BY cir.started_at DESC, cir.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countVisibleRunsForUser(array $authUser, array $filters = []): int
    {
        [$whereSql, $params] = $this->buildFilteredWhere($authUser, $filters);
        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM cdr_import_runs cir
             INNER JOIN phone_systems ps ON ps.id = cir.phone_system_id
             INNER JOIN clients c ON c.id = ps.client_id
             {$whereSql}"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getVisiblePhoneSystemOptionsForUser(array $authUser): array
    {
        if ($this->isPlatformUser($authUser)) {
            $stmt = db()->query(
                'SELECT ps.id, ps.system_name, ps.system_code, c.name AS client_name
                 FROM phone_systems ps
                 INNER JOIN clients c ON c.id = ps.client_id
                 ORDER BY c.name ASC, ps.system_name ASC'
            );
            return $stmt->fetchAll();
        }

        $stmt = db()->prepare(
            'SELECT ps.id, ps.system_name, ps.system_code, c.name AS client_name
             FROM phone_systems ps
             INNER JOIN clients c ON c.id = ps.client_id
             INNER JOIN user_client_access uca ON uca.client_id = ps.client_id
             WHERE uca.user_id = :user_id
             ORDER BY c.name ASC, ps.system_name ASC'
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return $stmt->fetchAll();
    }

    private function buildFilteredWhere(array $authUser, array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!$this->isPlatformUser($authUser)) {
            $clientIds = array_values(array_filter(array_map('intval', $authUser['accessible_client_ids'] ?? [])));
            if ($clientIds === []) {
                return ['WHERE 1 = 0', []];
            }

            $placeholders = [];
            foreach ($clientIds as $index => $clientId) {
                $key = ':client_' . $index;
                $placeholders[] = $key;
                $params[$key] = $clientId;
            }
            $clauses[] = 'ps.client_id IN (' . implode(', ', $placeholders) . ')';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $clauses[] = 'cir.status = :status';
            $params[':status'] = $status;
        }

        $phoneSystemId = (int) ($filters['phone_system_id'] ?? 0);
        if ($phoneSystemId > 0) {
            $clauses[] = 'cir.phone_system_id = :phone_system_id';
            $params[':phone_system_id'] = $phoneSystemId;
        }

        $clientId = (int) ($filters['client_id'] ?? 0);
        if ($clientId > 0) {
            $clauses[] = 'ps.client_id = :client_id';
            $params[':client_id'] = $clientId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $clauses[] = 'cir.started_at >= :date_from';
            $params[':date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $clauses[] = 'cir.started_at <= :date_to';
            $params[':date_to'] = $dateTo . ' 23:59:59';
        }

        $whereSql = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);
        return [$whereSql, $params];
    }

    private function isPlatformUser(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true);
    }
}
