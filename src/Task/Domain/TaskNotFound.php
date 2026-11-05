<?php

declare(strict_types=1);

namespace App\Task\Domain;

use RuntimeException;

final class TaskNotFound extends RuntimeException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('There is no task "%s".', $id));
    }
}
