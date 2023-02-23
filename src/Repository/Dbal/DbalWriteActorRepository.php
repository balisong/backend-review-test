<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Entity\Actor;
use App\Repository\WriteActorRepository;
use Doctrine\DBAL\Connection;

class DbalWriteActorRepository implements WriteActorRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Actor $actor): void
    {
        $this->connection->insert(
            'actor',
            [
                'id' => $actor->id(),
                'login' => $actor->login(),
                'url' => $actor->url(),
                'avatar_url' => $actor->avatarUrl()
            ]
        );
    }
}
