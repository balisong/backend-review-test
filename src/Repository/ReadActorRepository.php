<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Actor;

interface ReadActorRepository
{
    public function exist(int $id): bool;
}
