<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use Byfareska\Crud\Action\BulkAction;

/**
 * A bulk action of the application's own: marks the tasks chosen as done. Done by {@see MarkDoneHandler}.
 */
final readonly class MarkDone implements BulkAction
{
    public function __construct(
        public string $name = 'done',
        public ?string $role = null,
    ) {
    }

    public function handler(): string
    {
        return MarkDoneHandler::class;
    }
}
