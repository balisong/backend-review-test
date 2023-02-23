<?php

declare(strict_types=1);

namespace App\Repository\Dbal;

use App\Dto\EventInput;
use App\Entity\Event;
use App\Repository\WriteEventRepository;
use Doctrine\DBAL\Connection;

class DbalWriteEventRepository implements WriteEventRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create(Event $event): void
    {
        $this->connection->insert(
            'event',
            [
                'id' => $event->id(),
                'actor_id' => $event->actor()->id(),
                'repo_id' => $event->repo()->id(),
                'type' => $event->type(),
                'count' => $event->count(),
                'payload' => json_encode($event->payload()),
                'create_at' => $event->createAt()->format('Y-m-d H:i:s'),
                'comment' => $event->comment()
            ]
        );
    }

    public function update(EventInput $authorInput, int $id): void
    {
        $sql = <<<SQL
        UPDATE event
        SET comment = :comment
        WHERE id = :id
SQL;

        $this->connection->executeQuery($sql, ['id' => $id, 'comment' => $authorInput->comment]);
    }
}
