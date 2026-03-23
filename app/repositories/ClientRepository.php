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
}
