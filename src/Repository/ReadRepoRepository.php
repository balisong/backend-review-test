<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Repo;

interface ReadRepoRepository
{
    public function exist(int $id): bool;
}
