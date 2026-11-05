<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Tasks;
use Byfareska\Crud\UseCase\Query;

final readonly class ShowTask implements Query
{
    public function __construct(private Tasks $tasks)
    {
    }

    public function __invoke(TaskIdRequest $request): TaskView
    {
        return TaskView::of($this->tasks->get($request->id));
    }
}
