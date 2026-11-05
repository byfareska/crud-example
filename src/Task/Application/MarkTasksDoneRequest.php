<?php

declare(strict_types=1);

namespace App\Task\Application;

final readonly class MarkTasksDoneRequest
{
    /**
     * @param list<string> $ids
     */
    public function __construct(public array $ids)
    {
    }
}
