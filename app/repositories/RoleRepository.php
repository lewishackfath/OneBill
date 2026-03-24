<?php
declare(strict_types=1);

class RoleRepository
{
    public function all(): array
    {
        return db()->query('SELECT * FROM roles ORDER BY role_name ASC')->fetchAll();
    }
}
