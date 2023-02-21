<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Repository\ReadActorRepository;
use Doctrine\DBAL\Connection;

class DbalReadActorRepository implements ReadActorRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function exist(int $id): bool
    {
        $sql = <<<SQL
            SELECT 1
            FROM actor
            WHERE id = :id
        SQL;

        $result = $this->connection->fetchOne($sql, [
            'id' => $id,
        ]);

        return (bool) $result;
    }
}
