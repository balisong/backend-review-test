<?php

namespace App\Repository;

use App\Dto\EventInput;
use App\Entity\Event;

interface WriteEventRepository
{
    public function create(Event $event): void;
    public function update(EventInput $authorInput, int $id): void;
}
