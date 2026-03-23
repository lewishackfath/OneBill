<?php

declare(strict_types=1);

final class RoleRepository
{
    public function countAll(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    }

    public function getAll(): array
    {
        $stmt = db()->query('SELECT id, role_key, role_name, description FROM roles ORDER BY role_name ASC');
        return $stmt->fetchAll();
    }

    public function getAssignableRolesForUser(array $authUser): array
    {
        $roles = $authUser['roles'] ?? [];

        if (in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true)) {
            return $this->getAll();
        }

        if (in_array('client_admin', $roles, true)) {
            $stmt = db()->prepare("SELECT id, role_key, role_name, description FROM roles WHERE role_key IN ('client_admin','client_user','readonly') ORDER BY role_name ASC");
            $stmt->execute();
            return $stmt->fetchAll();
        }

        return [];
    }

    public function findByKey(string $roleKey): ?array
    {
        $stmt = db()->prepare('SELECT id, role_key, role_name, description FROM roles WHERE role_key = :role_key LIMIT 1');
        $stmt->execute([':role_key' => $roleKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
