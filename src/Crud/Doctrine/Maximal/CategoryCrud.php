<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Controller\CategorySummaryController;
use App\Entity\Category;
use Byfareska\Crud\Action\RouteAction;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Driver\Doctrine\DoctrineSource;
use Byfareska\Crud\Resource\AfterSave;
use Byfareska\Crud\Resource\CrudOperation;

/**
 * The categories of the products ({@see ProductCrud}), in the same panel: the form guessed from the mapping, the
 * action "summary" leading to a page of the application's own ({@see CategorySummaryController}, #[MapCrud]). No
 * preview drawer: a click on the name opens the details page. Categories are not deleted - the products point to
 * them: "operations" leaves Delete out, so there is no delete button, no bulk delete and no way to delete one by
 * address. One record at a time is up to a RecordGuard (see App\Crud\Dto\Maximal\DoneTaskGuard).
 *
 * A category is found by the names of its products too ("products.name": a page of the list is still one of
 * categories), and the column of how many products it has orders by a sort of the application's own
 * ({@see ProductCount}).
 */
#[AsCrud(
    name: 'category',
    path: '/categories',
    icon: 'tags',
    source: new DoctrineSource(
        Category::class,
        search: ['name', 'products.name'],
        sortable: ['name', 'description', 'productCount' => new ProductCount()],
        sort: ['name'],
        operations: [CrudOperation::List, CrudOperation::Read, CrudOperation::Create, CrudOperation::Update],
    ),
    columns: ['name', 'description', 'productCount'],
    section: 'app.nav.catalog',
    role: 'ROLE_ADMIN',
    priority: 20,
    actions: [
        new RouteAction('summary', 'app_category_summary', 'bar-chart'),
        // The list of its products, under it (CategoryProductsCrud): the id is the {category} of its path.
        new RouteAction('products', 'crud_category_product_list', 'box-seam', idParameter: 'category'),
    ],
    scope: 'admin',
    preview: false,
    // Of the optimistic locking, nothing to read.
    hidden: ['version'],
    // "Save" stays on the category (byfareska_crud.form.save); besides it "save and add another" (the add form),
    // "save and back to the list".
    afterSave: [AfterSave::New, AfterSave::Return],
    // The list page of its own: the theme's, with a note above it (templates/crud/category_list.html.twig).
    pageTemplates: ['list' => 'crud/category_list.html.twig'],
)]
final class CategoryCrud
{
}
