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

    private function getRoleKeysForUser(int $userId): array
    {
        $stmt = db()->prepare('SELECT r.role_key FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = :user_id ORDER BY r.role_name ASC');
        $stmt->execute([':user_id' => $userId]);
        return array_map(static fn(array $row): string => (string) $row['role_key'], $stmt->fetchAll());
    }
}
