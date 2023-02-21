<?php

declare(strict_types=1);

namespace App\Importer;

interface GitHubArchiveImporter
{
    public function import(\DateTimeInterface $importDateTime): iterable;
}
