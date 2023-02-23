<?php

declare(strict_types=1);

namespace App\Dto;

class GitHubArchiveImportMessage
{
    private \DateTimeImmutable $archiveDateTime;

    public function __construct(\DateTimeImmutable $archiveDateTime)
    {
        $this->archiveDateTime = $archiveDateTime;
    }

    public function getArchiveDateTime(): \DateTimeImmutable
    {
        return $this->archiveDateTime;
    }
}
