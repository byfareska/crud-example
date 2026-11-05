<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineFilter;

/**
 * A filter of the application's own, the bundle has none like it: the list keeps the records whose amount is at least
 * (or at most) the one typed - "price from" and "price to". Made by {@see PriceBoundHandler}.
 */
final readonly class PriceBound implements DoctrineFilter
{
    /**
     * @param string $field of the entity, e.g. "price"; a path through a relation too ("category.price")
     * @param bool $upper at most the amount typed instead of at least
     */
    public function __construct(
        public string $field,
        public bool $upper = false,
    ) {
    }

    public function handler(): string
    {
        return PriceBoundHandler::class;
    }
}
