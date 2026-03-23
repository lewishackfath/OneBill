<?php

declare(strict_types=1);

final class UserRepository
{
    public function findActiveByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT id, email, password_hash, first_name, last_name, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || (int) $user['is_active'] !== 1) {
            return null;
        }

        $user['roles'] = $this->getRoleKeysForUser((int) $user['id']);
        return $user;
    }

    public function updateLastLoginAt(int $userId): void
    {
        $stmt = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    public function countAll(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
    }

    public function listVisibleForUser(array $authUser): array
    {
        if ($this->isPlatformUser($authUser)) {
            $stmt = db()->query(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.is_active, u.last_login_at,
                        COALESCE((SELECT r.role_name
                                  FROM user_roles ur
                                  INNER JOIN roles r ON r.id = ur.role_id
                                  WHERE ur.user_id = u.id
                                  ORDER BY FIELD(r.role_key, 'super_admin','platform_admin','client_admin','client_user','readonly'), r.role_name
                                  LIMIT 1), '') AS primary_role_name,
                        (SELECT COUNT(*) FROM user_client_access uca WHERE uca.user_id = u.id) AS client_count
                 FROM users u
                 ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
            );
            return $stmt->fetchAll();
        }

        $stmt = db()->prepare(
            "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, u.is_active, u.last_login_at,
                    COALESCE((SELECT r.role_name
                              FROM user_roles ur
                              INNER JOIN roles r ON r.id = ur.role_id
                              WHERE ur.user_id = u.id
                              ORDER BY FIELD(r.role_key, 'super_admin','platform_admin','client_admin','client_user','readonly'), r.role_name
                              LIMIT 1), '') AS primary_role_name,
                    (SELECT COUNT(*) FROM user_client_access uca2 WHERE uca2.user_id = u.id) AS client_count
             FROM users u
             INNER JOIN user_client_access target_uca ON target_uca.user_id = u.id
             INNER JOIN user_client_access me_uca ON me_uca.client_id = target_uca.client_id
             WHERE me_uca.user_id = :user_id
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute([':user_id' => (int) $authUser['id']]);
        return $stmt->fetchAll();
    }

    public function findById(int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        $user['roles'] = $this->getRoleKeysForUser($userId);
        $user['client_access'] = $this->getClientAccess($userId);
        return $user;
    }

    public function findVisibleById(int $userId, array $authUser): ?array
    {
        $user = $this->findById($userId);
        if ($user === null) {
            return null;
        }

        if ($this->canManageUser($authUser, $user)) {
            return $user;
        }

        return null;
    }

    public function create(array $data): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (email, password_hash, first_name, last_name, is_active)
             VALUES (:email, :password_hash, :first_name, :last_name, :is_active)'
        );
        $stmt->execute([
            ':email' => normalise_email((string) $data['email']),
            ':password_hash' => $data['password_hash'],
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':is_active' => $data['is_active'],
        ]);

        return (int) db()->lastInsertId();
    }

    public function update(int $userId, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE users
             SET email = :email,
                 first_name = :first_name,
                 last_name = :last_name,
                 is_active = :is_active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $userId,
            ':email' => normalise_email((string) $data['email']),
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':is_active' => $data['is_active'],
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':id' => $userId,
            ':password_hash' => $passwordHash,
        ]);
    }

    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $email = normalise_email($email);
        if ($excludeUserId !== null) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
            $stmt->execute([':email' => $email, ':id' => $excludeUserId]);
            return (int) $stmt->fetchColumn() > 0;
        }

        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function syncSingleRole(int $userId, string $roleKey): void
    {
        $roleStmt = db()->prepare('SELECT id FROM roles WHERE role_key = :role_key LIMIT 1');
        $roleStmt->execute([':role_key' => $roleKey]);
        $roleId = $roleStmt->fetchColumn();
        if (!$roleId) {
            throw new RuntimeException('Invalid role selected.');
        }

        db()->prepare('DELETE FROM user_roles WHERE user_id = :user_id')->execute([':user_id' => $userId]);
        db()->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)')->execute([
            ':user_id' => $userId,
            ':role_id' => (int) $roleId,
        ]);
    }

    public function syncClientAccess(int $userId, array $assignments): void
    {
        db()->prepare('DELETE FROM user_client_access WHERE user_id = :user_id')->execute([':user_id' => $userId]);

        if ($assignments === []) {
            return;
        }

        $stmt = db()->prepare('INSERT INTO user_client_access (user_id, client_id, access_level) VALUES (:user_id, :client_id, :access_level)');
        foreach ($assignments as $assignment) {
            $stmt->execute([
                ':user_id' => $userId,
                ':client_id' => (int) $assignment['client_id'],
                ':access_level' => $assignment['access_level'],
            ]);
        }
    }

    public function getClientAccess(int $userId): array
    {
        $stmt = db()->prepare(
            'SELECT uca.client_id, uca.access_level, c.name AS client_name, c.status AS client_status
             FROM user_client_access uca
             INNER JOIN clients c ON c.id = uca.client_id
             WHERE uca.user_id = :user_id
             ORDER BY c.name ASC'
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getRoleKeysForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT r.role_key FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :user_id ORDER BY r.role_name ASC');
        $stmt->execute([':user_id' => $userId]);
        return array_map(static fn(array $row): string => (string) $row['role_key'], $stmt->fetchAll());
    }

    public function getPrimaryRoleNameForUser(int $userId): string
    {
        $stmt = db()->prepare(
            "SELECT r.role_name
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = :user_id
             ORDER BY FIELD(r.role_key, 'super_admin','platform_admin','client_admin','client_user','readonly'), r.role_name
             LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);
        return (string) ($stmt->fetchColumn() ?: '');
    }

    public function canManageUser(array $authUser, array $targetUser): bool
    {
        if ($this->isPlatformUser($authUser)) {
            return true;
        }

        $authRoles = $authUser['roles'] ?? [];
        if (!in_array('client_admin', $authRoles, true)) {
            return false;
        }

        $targetRoles = $targetUser['roles'] ?? [];
        foreach ($targetRoles as $role) {
            if (in_array($role, ['super_admin', 'platform_admin'], true)) {
                return false;
            }
        }

        $myClientIds = $this->getAccessibleClientIds((int) $authUser['id']);
        $targetClientIds = array_map(static fn(array $row): int => (int) $row['client_id'], $targetUser['client_access'] ?? []);

        return array_intersect($myClientIds, $targetClientIds) !== [];
    }

    public function getAccessibleClientIds(int $userId): array
    {
        $stmt = db()->prepare('SELECT client_id FROM user_client_access WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        return array_map(static fn(array $row): int => (int) $row['client_id'], $stmt->fetchAll());
    }

    private function isPlatformUser(array $authUser): bool
    {
        $roles = $authUser['roles'] ?? [];
        return in_array('super_admin', $roles, true) || in_array('platform_admin', $roles, true);
    }
}
