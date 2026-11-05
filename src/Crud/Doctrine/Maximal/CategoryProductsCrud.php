<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Driver\Doctrine\DoctrineSource;
use Byfareska\Crud\Resource\CrudOperation;

/**
 * The products of one category, under it: /admin/categories/{category}/products (and the details of one of them under
 * that). A parameter of the path of the application's own - the bundle hands it on to every address it makes on these
 * pages and in their live requests (PageParameters), and ProductsOfTheCategory narrows the list by it. Reached from
 * a category (the action "products" of CategoryCrud), not from the menu nor the palette.
 */
#[AsCrud(
    name: 'category_product',
    path: '/categories/{category}/products',
    icon: 'box-seam',
    source: new DoctrineSource(
        Product::class,
        search: ['name', 'sku'],
        sort: ['name'],
        operations: [CrudOperation::List, CrudOperation::Read],
    ),
    columns: ['name', 'sku', 'price' => 'money', 'stock'],
    role: 'ROLE_ADMIN',
    scope: 'admin',
    preview: false,
    // More than the 100 the bundle once allowed: the largest size offered is the most a page has.
    perPage: [10, 150],
)]
final class CategoryProductsCrud
{
}
