<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Request;

use App\Task\Application\MarkTasksDone;
use App\Task\Application\MarkTasksDoneRequest;
use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\MapsToRequest;

/**
 * No form: the ids are those chosen in the list (App\Crud\Dto\Maximal\MarkDoneHandler).
 */
#[UseCase(MarkTasksDone::class)]
final class MarkTasksDoneDto implements MapsToRequest
{
    /** @var list<string> */
    public array $ids = [];

    public function toRequest(): MarkTasksDoneRequest
    {
        return new MarkTasksDoneRequest($this->ids);
    }
}
