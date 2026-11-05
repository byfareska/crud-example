<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\DoneTaskAdded;
use App\Task\Domain\Task;
use App\Task\Domain\Tasks;
use Byfareska\Crud\UseCase\Command;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class SaveTask implements Command
{
    public function __construct(private Tasks $tasks)
    {
    }

    /**
     * Returns the task: the panel goes to the page of a new one by its id.
     *
     * @throws DoneTaskAdded
     */
    public function __invoke(SaveTaskRequest $request): TaskView
    {
        if ($request->id === null) {
            if ($request->done) {
                throw new DoneTaskAdded('A new task is not done yet.');
            }

            $task = new Task(Uuid::v7()->toRfc4122(), $request->title, $request->priority, $request->done, new DateTimeImmutable(), $request->attachments);
        } else {
            $task = $this->tasks->get($request->id);
            $task->title = $request->title;
            $task->priority = $request->priority;
            $task->done = $request->done;
            $task->attachments = $request->attachments;
        }
        $this->tasks->save($task);

        return TaskView::of($task);
    }
}
