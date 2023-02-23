<?php

declare(strict_types=1);

namespace App\Repository;

interface ReadRepoRepository
{
    public function exist(int $id): bool;
}
