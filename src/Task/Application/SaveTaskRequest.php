<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Priority;

/**
 * A new task (no id) or the changes of one.
 */
final readonly class SaveTaskRequest
{
    public function __construct(
        public ?string $id,
        public string $title,
        public Priority $priority,
        public bool $done,
        /** @var list<string> */
        public array $attachments = [],
    ) {
    }
}
