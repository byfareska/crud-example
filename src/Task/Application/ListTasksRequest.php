<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Priority;
use Byfareska\Crud\UseCase\Sort;

final readonly class ListTasksRequest
{
    /**
     * @param list<Sort> $sort
     */
    public function __construct(
        public string $query,
        public ?bool $done,
        public ?Priority $priority,
        public array $sort,
        public int $page,
        public int $perPage,
    ) {
    }
}
