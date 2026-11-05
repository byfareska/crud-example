<?php

declare(strict_types=1);

namespace App\Task\Infrastructure;

use App\Task\Domain\Priority;
use App\Task\Domain\Task;
use App\Task\Domain\TaskNotFound;
use App\Task\Domain\Tasks;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The tasks in one JSON file - no database at all, to show that the "dto" driver does not care where records are.
 */
final readonly class JsonFileTasks implements Tasks
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/tasks_%kernel.environment%.json')]
        private string $file,
    ) {
    }

    public function all(): array
    {
        return array_values($this->read());
    }

    public function get(string $id): Task
    {
        return $this->read()[$id] ?? throw TaskNotFound::withId($id);
    }

    public function save(Task $task): void
    {
        $tasks = $this->read();
        $tasks[$task->id] = $task;
        $this->write($tasks);
    }

    public function remove(string $id): void
    {
        $tasks = $this->read();
        if (!isset($tasks[$id])) {
            throw TaskNotFound::withId($id);
        }
        unset($tasks[$id]);
        $this->write($tasks);
    }

    /** @return array<string, Task> */
    private function read(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $tasks = [];
        foreach (json_decode((string) file_get_contents($this->file), true, flags: JSON_THROW_ON_ERROR) as $row) {
            $tasks[$row['id']] = new Task($row['id'], $row['title'], Priority::from($row['priority']), $row['done'], new DateTimeImmutable($row['createdAt']), $row['attachments'] ?? []);
        }

        return $tasks;
    }

    /** @param array<string, Task> $tasks */
    private function write(array $tasks): void
    {
        $rows = array_map(static fn (Task $task): array => [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority->value,
            'done' => $task->done,
            'createdAt' => $task->createdAt->format(DateTimeInterface::ATOM),
            'attachments' => $task->attachments,
        ], array_values($tasks));
        file_put_contents($this->file, json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX);
    }
}
