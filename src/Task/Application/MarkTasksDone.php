<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Task;
use App\Task\Domain\Tasks;
use Byfareska\Crud\UseCase\Command;

final readonly class MarkTasksDone implements Command
{
    public function __construct(private Tasks $tasks)
    {
    }

    /**
     * Returns how many tasks were not done before; an id of no task is passed over.
     */
    public function __invoke(MarkTasksDoneRequest $request): int
    {
        $marked = 0;
        foreach ($this->tasks->all() as $task) {
            if (!$task->done && in_array($task->id, $request->ids, true)) {
                $task->done = true;
                $this->tasks->save($task);
                ++$marked;
            }
        }

        return $marked;
    }
}
