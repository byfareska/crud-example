<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Request;

use App\Task\Application\ShowTask;
use App\Task\Application\TaskIdRequest;
use App\Task\Domain\TaskNotFound;
use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\Failure;
use Byfareska\Crud\UseCase\MapsToRequest;

#[UseCase(ShowTask::class, errors: [TaskNotFound::class => new Failure(404, message: 'crud.error.not_found')])]
final class ShowTaskDto implements MapsToRequest
{
    public string $id;

    public function toRequest(): TaskIdRequest
    {
        return new TaskIdRequest($this->id);
    }
}
