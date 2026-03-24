<?php

declare(strict_types=1);

final class ClientRepository
{
    public function countAll(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM clients')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();
    }

    public function countVisibleForUser(array $authUser): int
    {
        if ($this->isPlatformUser($authUser)) {
            return $this->countAll();
        }

        $stmt = db()->prepare('SELECT COUNT(*) FROM user_client_access WHERE user_id = :user_id');
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return (int) $stmt->fetchColumn();
    }

    public function countActiveVisibleForUser(array $authUser): int
    {
        if ($this->isPlatformUser($authUser)) {
            return $this->countActive();
        }

        $stmt = db()->prepare(
            "SELECT COUNT(*)
             FROM clients c
             INNER JOIN user_client_access uca ON uca.client_id = c.id
             WHERE uca.user_id = :user_id AND c.status = 'active'"
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return (int) $stmt->fetchColumn();
    }

    public function getAll(): array
    {
        $stmt = db()->query('SELECT * FROM clients ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    public function getAllVisibleForUser(array $authUser): array
    {
        if ($this->isPlatformUser($authUser)) {
            return $this->getAll();
        }

        $stmt = db()->prepare(
            'SELECT c.*, uca.access_level
             FROM clients c
             INNER JOIN user_client_access uca ON uca.client_id = c.id
             WHERE uca.user_id = :user_id
             ORDER BY c.name ASC'
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return $stmt->fetchAll();
    }

    public function getOptionsForUser(array $authUser): array
    {
        $rows = $this->getAllVisibleForUser($authUser);
        return array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'status' => (string) $row['status'],
        ], $rows);
    }

    public function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findVisibleById(int $id, array $authUser): ?array
    {
        if ($this->isPlatformUser($authUser)) {
            return $this->findById($id);
        }

        $stmt = db()->prepare(
            'SELECT c.*, uca.access_level
             FROM clients c
             INNER JOIN user_client_access uca ON uca.client_id = c.id
             WHERE c.id = :id AND uca.user_id = :user_id
             LIMIT 1'
        );
        $stmt->execute([
            ':id' => $id,
            ':user_id' => (int) $authUser['id'],
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO clients (name, code, status, timezone, contact_name, contact_email, contact_phone, notes)
             VALUES (:name, :code, :status, :timezone, :contact_name, :contact_email, :contact_phone, :notes)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':code' => $data['code'],
            ':status' => $data['status'],
            ':timezone' => $data['timezone'],
            ':contact_name' => $data['contact_name'] ?: null,
            ':contact_email' => $data['contact_email'] ?: null,
            ':contact_phone' => $data['contact_phone'] ?: null,
            ':notes' => $data['notes'] ?: null,
        ]);

        return (int) db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE clients
             SET name = :name,
                 code = :code,
                 status = :status,
                 timezone = :timezone,
                 contact_name = :contact_name,
                 contact_email = :contact_email,
                 contact_phone = :contact_phone,
                 notes = :notes,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':code' => $data['code'],
            ':status' => $data['status'],
            ':timezone' => $data['timezone'],
            ':contact_name' => $data['contact_name'] ?: null,
            ':contact_email' => $data['contact_email'] ?: null,
            ':contact_phone' => $data['contact_phone'] ?: null,
            ':notes' => $data['notes'] ?: null,
        ]);
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM clients WHERE code = :code AND id <> :id');
            $stmt->execute([':code' => $code, ':id' => $excludeId]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = db()->prepare('SELECT COUNT(*) FROM clients WHERE code = :code');
        $stmt->execute([':code' => $code]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function canManageClients(array $authUser): bool
    {
        return $this->isPlatformUser($authUser);
    }

    private function isPlatformUser(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true);
    }
}
