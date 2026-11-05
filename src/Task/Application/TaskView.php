<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Task;
use DateTimeInterface;

/**
 * A task as the use cases return it: its public properties are the record the panel shows and fills the edit form with.
 */
final readonly class TaskView
{
    public function __construct(
        public string $id,
        public string $title,
        public string $priority,
        public bool $done,
        public string $createdAt,
        /** @var list<string> references of its files, which the panel shows as files */
        public array $attachments = [],
    ) {
    }

    public static function of(Task $task): self
    {
        return new self($task->id, $task->title, $task->priority->value, $task->done, $task->createdAt->format(DateTimeInterface::ATOM), $task->attachments);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
