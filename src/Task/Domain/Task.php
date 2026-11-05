<?php

declare(strict_types=1);

namespace App\Task\Domain;

use DateTimeImmutable;

final class Task
{
    public function __construct(
        public readonly string $id,
        public string $title,
        public Priority $priority,
        public bool $done,
        public readonly DateTimeImmutable $createdAt,
        /** @var list<string> references of its files: "private://ab/cd/....pdf" */
        public array $attachments = [],
    ) {
    }
}
