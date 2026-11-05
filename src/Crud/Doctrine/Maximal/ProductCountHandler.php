<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Driver\Doctrine\DoctrineListQuery;
use Byfareska\Crud\Driver\Doctrine\DoctrineSort;
use Byfareska\Crud\Driver\Doctrine\DoctrineSortHandler;

/**
 * Orders the categories by the number of their products ({@see ProductCount}): selected as HIDDEN, so the list still
 * has the entities alone.
 */
final readonly class ProductCountHandler implements DoctrineSortHandler
{
    public function apply(DoctrineSort $sort, DoctrineListQuery $query, bool $descending): void
    {
        $alias = $query->parameter('products');
        $query->query
            ->addSelect(sprintf(
                '(SELECT COUNT(%1$s.id) FROM %2$s %1$s WHERE %1$s.category = e) AS HIDDEN %1$s_count',
                $alias,
                Product::class,
            ))
            ->addOrderBy($alias . '_count', $descending ? 'DESC' : 'ASC');
    }
}
