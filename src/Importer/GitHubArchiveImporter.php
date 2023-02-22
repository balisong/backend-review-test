<?php

declare(strict_types=1);

namespace App\Importer;

interface GitHubArchiveImporter
{
    /** @return iterable<array<string, mixed>> */
    public function import(\DateTimeInterface $importDateTime): iterable;
}
