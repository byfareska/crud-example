<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Action\BulkAction;

/**
 * A bulk action of the application's own: takes the products chosen off sale. Done by
 * {@see DeactivateProductsHandler}.
 */
final readonly class DeactivateProducts implements BulkAction
{
    public function __construct(
        public string $name = 'deactivate',
        public ?string $role = null,
    ) {
    }

    public function handler(): string
    {
        return DeactivateProductsHandler::class;
    }
}
