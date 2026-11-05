<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Tasks;
use Byfareska\Crud\UseCase\Command;

final readonly class DeleteTask implements Command
{
    public function __construct(private Tasks $tasks)
    {
    }

    public function __invoke(TaskIdRequest $request): void
    {
        $this->tasks->remove($request->id);
    }
}
