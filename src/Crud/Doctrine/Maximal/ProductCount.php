<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineSort;

/**
 * An order of the application's own, the bundle has none like it: the categories by how many products they have - a
 * subquery, no field of the entity. Made by {@see ProductCountHandler}.
 */
final readonly class ProductCount implements DoctrineSort
{
    public function handler(): string
    {
        return ProductCountHandler::class;
    }
}
