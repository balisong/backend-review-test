<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Entity\Repo;
use App\Repository\WriteRepoRepository;
use Doctrine\DBAL\Connection;

class DbalWriteRepoRepository implements WriteRepoRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Repo $repo): void
    {
        $this->connection->insert(
            'repo',
            [
                'id' => $repo->id(),
                'name' => $repo->name(),
                'url' => $repo->url()
            ]
        );
    }
}
