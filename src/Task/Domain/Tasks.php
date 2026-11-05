<?php

declare(strict_types=1);

namespace App\Task\Domain;

/**
 * Where the tasks are kept. The panel does not know: it only runs the use cases.
 */
interface Tasks
{
    /** @return list<Task> */
    public function all(): array;

    /** @throws TaskNotFound */
    public function get(string $id): Task;

    public function save(Task $task): void;

    /** @throws TaskNotFound */
    public function remove(string $id): void;
}
