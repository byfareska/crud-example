<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineListExtension;
use Byfareska\Crud\Driver\Doctrine\DoctrineListQuery;

/**
 * Reads the category of the products of the list in the same query: the column prints its name.
 */
final readonly class ProductCategoryJoin implements DoctrineListExtension
{
    public function resource(): string
    {
        return 'product';
    }

    public function apply(DoctrineListQuery $query): void
    {
        $query->query->addSelect($query->join('category'));
    }
}
