<?php
declare(strict_types=1);

class ClientRepository
{
    public function allActive(): array
    {
        return db()->query("SELECT * FROM clients WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    }

    public function countVisibleToCurrentUser(): int
    {
        if (user_has_role(['super_admin', 'platform_admin'])) {
            return (int) db()->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        }

        return count(available_client_ids_for_user());
    }

    public function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        return $client ?: null;
    }

    public function findAccessibleForCurrentUser(): array
    {
        if (user_has_role(['super_admin', 'platform_admin'])) {
            return $this->allActive();
        }

        $clientIds = available_client_ids_for_user();
        if (empty($clientIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
        $sql = "SELECT * FROM clients WHERE id IN ($placeholders) AND status = 'active' ORDER BY name ASC";
        $stmt = db()->prepare($sql);
        $stmt->execute($clientIds);
        return $stmt->fetchAll();
    }
}
