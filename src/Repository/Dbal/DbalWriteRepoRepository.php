<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Entity\Repo;
use App\Repository\ReadRepoRepository;
use App\Repository\WriteRepoRepository;
use Doctrine\DBAL\Connection;

class DbalWriteRepoRepository implements WriteRepoRepository
{
    private Connection $connection;

//    private ReadRepoRepository $readRepoRepository;

    public function __construct(Connection $connection/*, ReadRepoRepository $readRepoRepository*/)
    {
        $this->connection = $connection;
//        $this->readRepoRepository = $readRepoRepository;
    }

    public function create(Repo $repo): void
    {
//        if (!$this->readRepoRepository->exist($repo->id())) {
            $this->connection->insert(
                'repo',
                [
                    'id' => $repo->id(),
                    'name' => $repo->name(),
                    'url' => $repo->url()
                ]
            );
//        }
    }
}
