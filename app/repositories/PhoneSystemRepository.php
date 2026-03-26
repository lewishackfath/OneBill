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
                api_username, api_password, api_token, timezone, status, notes
             ) VALUES (
                :client_id, :system_name, :system_code, :base_url, :auth_type,
                :api_username, :api_password, :api_token, :timezone, :status, :notes
             )'
        );
        $stmt->execute([
            ':client_id' => (int) $data['client_id'],
            ':system_name' => $data['system_name'],
            ':system_code' => $data['system_code'],
            ':base_url' => $data['base_url'],
            ':auth_type' => $data['auth_type'],
            ':api_username' => $data['api_username'] !== '' ? $data['api_username'] : null,
            ':api_password' => $data['api_password'] !== '' ? $data['api_password'] : null,
            ':api_token' => $data['api_token'] !== '' ? $data['api_token'] : null,
            ':timezone' => $data['timezone'],
            ':status' => $data['status'],
            ':notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ]);

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
            'updated_at = NOW()',
        ];

        $params = [
            ':id' => $id,
            ':client_id' => (int) $data['client_id'],
            ':system_name' => $data['system_name'],
            ':system_code' => $data['system_code'],
            ':base_url' => $data['base_url'],
            ':auth_type' => $data['auth_type'],
            ':api_username' => $data['api_username'] !== '' ? $data['api_username'] : null,
            ':timezone' => $data['timezone'],
            ':status' => $data['status'],
            ':notes' => $data['notes'] !== '' ? $data['notes'] : null,
        ];

        if (array_key_exists('api_password', $data)) {
            $fields[] = 'api_password = :api_password';
            $params[':api_password'] = $data['api_password'] !== '' ? $data['api_password'] : null;
        }

        if (array_key_exists('api_token', $data)) {
            $fields[] = 'api_token = :api_token';
            $params[':api_token'] = $data['api_token'] !== '' ? $data['api_token'] : null;
        }

        $sql = 'UPDATE phone_systems SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
    }

    public function recordConnectionTest(int $id, bool $success): void
    {
        $stmt = db()->prepare(
            'UPDATE phone_systems
             SET last_tested_at = NOW(),
                 last_test_status = :status,
                 updated_at = updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':status' => $success ? 'success' : 'failed',
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

    private function isPlatformUser(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true);
    }
}
