<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Repo;

interface WriteRepoRepository
{
    public function create(Repo $repo): void;
}
