<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Request;

use App\Task\Application\SaveTask;
use App\Task\Application\SaveTaskRequest;
use App\Task\Domain\Priority;
use App\Task\Domain\TaskNotFound;
use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\Failure;
use Symfony\Component\Form\Attribute\AsFormType;

/**
 * The fields of a new task, and the id (no field: the panel puts it from the address).
 */
#[AsFormType]
#[UseCase(SaveTask::class, errors: [TaskNotFound::class => new Failure(404, message: 'crud.error.not_found')])]
final class UpdateTaskDto extends CreateTaskDto
{
    public string $id;

    public function toRequest(): SaveTaskRequest
    {
        return new SaveTaskRequest($this->id, $this->title, Priority::from($this->priority), $this->done, $this->attachments);
    }
}
