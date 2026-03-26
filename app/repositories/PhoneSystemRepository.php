<?php

declare(strict_types=1);

final class PhoneSystemRepository
{
    public function countVisibleForUser(array $authUser): int
    {
        if ($this->isPlatformUser($authUser)) {
            return (int) db()->query('SELECT COUNT(*) FROM phone_systems')->fetchColumn();
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*)
             FROM phone_systems ps
             INNER JOIN user_client_access uca ON uca.client_id = ps.client_id
             WHERE uca.user_id = :user_id'
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return (int) $stmt->fetchColumn();
    }

    public function countActiveVisibleForUser(array $authUser): int
    {
        if ($this->isPlatformUser($authUser)) {
            return (int) db()->query("SELECT COUNT(*) FROM phone_systems WHERE status = 'active'")->fetchColumn();
        }

        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM phone_systems ps
             INNER JOIN user_client_access uca ON uca.client_id = ps.client_id
             WHERE uca.user_id = :user_id
               AND ps.status = 'active'"
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return (int) $stmt->fetchColumn();
    }

    public function getAllVisibleForUser(array $authUser): array
    {
        if ($this->isPlatformUser($authUser)) {
            $stmt = db()->query(
                'SELECT ps.*, c.name AS client_name
                 FROM phone_systems ps
                 INNER JOIN clients c ON c.id = ps.client_id
                 ORDER BY c.name ASC, ps.system_name ASC'
            );
            return $stmt->fetchAll();
        }

        $stmt = db()->prepare(
            'SELECT ps.*, c.name AS client_name, uca.access_level AS client_access_level
             FROM phone_systems ps
             INNER JOIN clients c ON c.id = ps.client_id
             INNER JOIN user_client_access uca ON uca.client_id = ps.client_id
             WHERE uca.user_id = :user_id
             ORDER BY c.name ASC, ps.system_name ASC'
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return $stmt->fetchAll();
    }

    public function getActivePassiveSocketSystems(): array
    {
        $stmt = db()->query(
            "SELECT ps.*, c.name AS client_name
             FROM phone_systems ps
             INNER JOIN clients c ON c.id = ps.client_id
             WHERE ps.status = 'active'
               AND COALESCE(ps.cdr_enabled, 0) = 1
               AND COALESCE(ps.connection_mode, 'passive_socket') = 'passive_socket'
               AND COALESCE(ps.host, '') <> ''
               AND COALESCE(ps.port, 0) > 0
             ORDER BY ps.id ASC"
        );

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT ps.*, c.name AS client_name
             FROM phone_systems ps
             INNER JOIN clients c ON c.id = ps.client_id
             WHERE ps.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findVisibleById(int $id, array $authUser): ?array
    {
        $row = $this->findById($id);
        if ($row === null) {
            return null;
        }

        return $this->canManagePhoneSystem($authUser, $row) ? $row : null;
    }

    public function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO phone_systems (
                client_id, system_name, system_code, base_url, auth_type,
                api_username, api_password, api_token, timezone, status, notes,
                host, port, connection_mode, cdr_enabled, cdr_field_profile, socket_timeout_seconds
             ) VALUES (
                :client_id, :system_name, :system_code, :base_url, :auth_type,
                :api_username, :api_password, :api_token, :timezone, :status, :notes,
                :host, :port, :connection_mode, :cdr_enabled, :cdr_field_profile, :socket_timeout_seconds
             )'
        );
        $stmt->execute($this->normaliseWriteParams($data));

        return (int) db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $fields = [
            'client_id = :client_id',
            'system_name = :system_name',
            'system_code = :system_code',
            'base_url = :base_url',
            'auth_type = :auth_type',
            'api_username = :api_username',
            'timezone = :timezone',
            'status = :status',
            'notes = :notes',
            'host = :host',
            'port = :port',
            'connection_mode = :connection_mode',
            'cdr_enabled = :cdr_enabled',
            'cdr_field_profile = :cdr_field_profile',
            'socket_timeout_seconds = :socket_timeout_seconds',
            'updated_at = NOW()',
        ];

        $params = $this->normaliseWriteParams($data);
        $params[':id'] = $id;

        if (array_key_exists('api_password', $data)) {
            $fields[] = 'api_password = :api_password';
        } else {
            unset($params[':api_password']);
        }

        if (array_key_exists('api_token', $data)) {
            $fields[] = 'api_token = :api_token';
        } else {
            unset($params[':api_token']);
        }

        $sql = 'UPDATE phone_systems SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    }

    public function recordConnectionTest(int $id, bool $success, string $message = ''): void
    {
        $stmt = db()->prepare(
            'UPDATE phone_systems
             SET last_tested_at = NOW(),
                 last_test_status = :test_status,
                 last_connect_at = NOW(),
                 last_connect_status = :connect_status,
                 last_connect_message = :connect_message,
                 updated_at = updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':test_status' => $success ? 'success' : 'failed',
            ':connect_status' => $success ? 'success' : 'failed',
            ':connect_message' => $message !== '' ? $message : null,
        ]);
    }

    public function updateLastCollectorStatus(int $id, bool $success, string $message = ''): void
    {
        $stmt = db()->prepare(
            'UPDATE phone_systems
             SET last_connect_at = NOW(),
                 last_connect_status = :status,
                 last_connect_message = :message,
                 updated_at = updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':status' => $success ? 'success' : 'failed',
            ':message' => $message !== '' ? $message : null,
        ]);
    }

    public function codeExistsForClient(int $clientId, string $systemCode, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = db()->prepare(
                'SELECT COUNT(*) FROM phone_systems
                 WHERE client_id = :client_id AND system_code = :system_code AND id <> :id'
            );
            $stmt->execute([
                ':client_id' => $clientId,
                ':system_code' => $systemCode,
                ':id' => $excludeId,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = db()->prepare(
            'SELECT COUNT(*) FROM phone_systems
             WHERE client_id = :client_id AND system_code = :system_code'
        );
        $stmt->execute([
            ':client_id' => $clientId,
            ':system_code' => $systemCode,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function canManagePhoneSystems(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true)
            || in_array('platform_admin', $roles, true)
            || in_array('client_admin', $roles, true);
    }

    public function canManagePhoneSystem(array $authUser, array $phoneSystem): bool
    {
        if (!$this->canManagePhoneSystems($authUser)) {
            return false;
        }

        if ($this->isPlatformUser($authUser)) {
            return true;
        }

        $accessibleClientIds = array_map(static fn(array $row): int => (int) $row['id'], $authUser['accessible_clients'] ?? []);
        return in_array((int) $phoneSystem['client_id'], $accessibleClientIds, true);
    }

    private function normaliseWriteParams(array $data): array
    {
        return [
            ':client_id' => (int) $data['client_id'],
            ':system_name' => $data['system_name'],
            ':system_code' => $data['system_code'],
            ':base_url' => $data['base_url'] !== '' ? $data['base_url'] : null,
            ':auth_type' => $data['auth_type'],
            ':api_username' => $data['api_username'] !== '' ? $data['api_username'] : null,
            ':api_password' => array_key_exists('api_password', $data) ? ($data['api_password'] !== '' ? $data['api_password'] : null) : null,
            ':api_token' => array_key_exists('api_token', $data) ? ($data['api_token'] !== '' ? $data['api_token'] : null) : null,
            ':timezone' => $data['timezone'],
            ':status' => $data['status'],
            ':notes' => $data['notes'] !== '' ? $data['notes'] : null,
            ':host' => $data['host'] !== '' ? $data['host'] : null,
            ':port' => (int) $data['port'],
            ':connection_mode' => $data['connection_mode'],
            ':cdr_enabled' => !empty($data['cdr_enabled']) ? 1 : 0,
            ':cdr_field_profile' => $data['cdr_field_profile'] !== '' ? $data['cdr_field_profile'] : null,
            ':socket_timeout_seconds' => max(2, (int) $data['socket_timeout_seconds']),
        ];
    }

    private function isPlatformUser(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true);
    }
}
