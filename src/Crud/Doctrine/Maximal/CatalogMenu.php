<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Navigation\NavigationExtension;
use Byfareska\Crud\Navigation\NavigationItem;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The menu of the admin panel, changed after the bundle made it (NavigationExtension): the products show how many
 * are off sale - counted only when the menu is drawn - and the catalog section leads to the documentation, an
 * address of another site.
 */
final readonly class CatalogMenu implements NavigationExtension
{
    public function __construct(
        private EntityManagerInterface $entities,
    ) {
    }

    public function extend(array $items): array
    {
        $items = array_map(fn (NavigationItem $item): NavigationItem => $item->resource === 'product'
            ? $item->with(badge: fn (): int => $this->entities->getRepository(Product::class)->count(['active' => false]))
            : $item, $items);
        $items[] = new NavigationItem(
            'app.nav.catalog',
            'app_docs',
            'app.nav.docs',
            'book',
            priority: 100,
            scope: 'admin',
            url: 'https://symfony.com/doc/current/index.html',
            target: '_blank',
        );

        return $items;
    }
}
