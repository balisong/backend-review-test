<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Entity\Actor;
use App\Repository\ReadActorRepository;
use App\Repository\WriteActorRepository;
use Doctrine\DBAL\Connection;

class DbalWriteActorRepository implements WriteActorRepository
{
    private Connection $connection;

//    private ReadActorRepository $readActorRepository;

    public function __construct(Connection $connection/*, ReadActorRepository $readActorRepository*/)
    {
        $this->connection = $connection;
//        $this->readActorRepository = $readActorRepository;
    }

    public function create(Actor $actor): void
    {
//        if (!$this->readActorRepository->exist($actor->id())) {
            $this->connection->insert(
                'actor',
                [
                    'id' => $actor->id(),
                    'login' => $actor->login(),
                    'url' => $actor->url(),
                    'avatar_url' => $actor->avatarUrl()
                ]
            );
//        }
    }
}
