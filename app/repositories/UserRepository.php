<?php
declare(strict_types=1);

class UserRepository
{
    public function countVisibleToCurrentUser(): int
    {
        if (user_has_role(['super_admin', 'platform_admin'])) {
            return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        }

        $clientIds = available_client_ids_for_user();
        if (empty($clientIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        $sql = "SELECT COUNT(DISTINCT u.id)
                FROM users u
                INNER JOIN user_client_access uca ON uca.user_id = u.id
                WHERE uca.client_id IN ($placeholders)";
        $stmt = db()->prepare($sql);
        $stmt->execute($clientIds);
        return (int) $stmt->fetchColumn();
    }

    public function findWithRoleAndClients(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $roleStmt = db()->prepare('
            SELECT r.id, r.role_key, r.role_name
            FROM roles r
            INNER JOIN user_roles ur ON ur.role_id = r.id
            WHERE ur.user_id = ?
            ORDER BY r.role_name ASC
        ');
        $roleStmt->execute([$id]);
        $roles = $roleStmt->fetchAll();
        $user['roles'] = $roles;
        $user['role_keys'] = array_values(array_column($roles, 'role_key'));

        $clientStmt = db()->prepare('
            SELECT uca.client_id, uca.access_level, c.name AS client_name
            FROM user_client_access uca
            INNER JOIN clients c ON c.id = uca.client_id
            WHERE uca.user_id = ?
            ORDER BY c.name ASC
        ');
        $clientStmt->execute([$id]);
        $user['client_access'] = $clientStmt->fetchAll();

        return $user;
    }
}
