<?php

declare(strict_types=1);

final class RoleRepository
{
    public function countAll(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM roles')->fetchColumn();
    }
}
