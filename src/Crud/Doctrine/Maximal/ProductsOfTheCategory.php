<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineQuery;
use Byfareska\Crud\Driver\Doctrine\DoctrineScope;
use Byfareska\Crud\Routing\PageParameters;

/**
 * The products of CategoryProductsCrud are those of the category in its address ({category}): listed, and found by
 * their id - another category's product is not there. No category, nothing.
 */
final readonly class ProductsOfTheCategory implements DoctrineScope
{
    public function __construct(
        private PageParameters $page,
    ) {
    }

    public function resource(): string
    {
        return 'category_product';
    }

    public function apply(DoctrineQuery $query): void
    {
        $category = $this->page->get('category');
        if ($category === null || !ctype_digit($category)) {
            $query->query->andWhere('1 = 0');

            return;
        }

        $parameter = $query->parameter('category');
        $query->query->andWhere(sprintf('IDENTITY(e.category) = :%s', $parameter))->setParameter($parameter, (int) $category);
    }
}
