<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Actor;

interface WriteActorRepository
{
    public function create(Actor $actor): void;
}
