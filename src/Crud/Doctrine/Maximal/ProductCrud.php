<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use App\Form\ProductType;
use Byfareska\Crud\Action\BulkDeleteAction;
use Byfareska\Crud\Action\ConfirmInput;
use Byfareska\Crud\Action\DuplicateAction;
use Byfareska\Crud\Action\EditAction;
use Byfareska\Crud\Action\RunAction;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Driver\Doctrine\DoctrineForm;
use Byfareska\Crud\Driver\Doctrine\DoctrineSource;
use Byfareska\Crud\Driver\Doctrine\FieldFilter;
use Byfareska\Crud\Filter\Comparison;
use Byfareska\Crud\Layout\Fieldset;
use Byfareska\Crud\Layout\Row;
use Byfareska\Crud\Value\ComponentFormat;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

/**
 * The doctrine driver with everything: a form type of the application, filters (also of the application's own:
 * PriceBound), search over a relation, sorting by a relation, a default order, a layout of the form, a theme for the
 * price, a component printing the stock (ComponentFormat), an action of a kind of the application's own (CopyAction)
 * a change of the query of the list (ProductCategoryJoin), the products the panel has at all (ProductsAddedSoFar), the
 * rows of what is not for sale dimmed (InactiveProductRow), bulk actions: the bundle's deleting and one of the
 * application's own (DeactivateProducts) - and a list of its own (ProductList) with a live action more, put on sale
 * and taken off from the row menu (RunAction, done by ToggleProductActive).
 *
 * Each filter but PriceBound says how it compares: the bundle's guess by the mapping (the category is searched as you
 * type, "stock" =, ≥, ≤...), a form type ("createdAt" from - to with the time as well: DateTimeType; without it the
 * days only) or a FieldFilter offering what is chosen here (the SKU by its beginning). The category and the date are
 * always in view, the rest behind "more filters".
 *
 * What can be edited is the form type (ProductType); a field of it can depend on the record - "active" is disabled for
 * a product out of stock - and the edit form gives it an option of its own (updateForm): the SKU is not changed. The rule behind it is a constraint of the entity (Product::validateActive()), shown at its
 * field.
 */
#[AsCrud(
    name: 'product',
    path: '/products',
    icon: 'box-seam',
    source: new DoctrineSource(
        Product::class,
        form: ProductType::class,
        // The same type for editing, with the SKU it was given fixed.
        filters: [
            // Also "is one of" several categories, and "is empty": products without one.
            'category' => new FieldFilter('category', Comparison::CHOICES),
            'active',
            'createdAt' => DateTimeType::class,
            'stock',
            // Its type is guessed; the options given go to the field of the value (an SKU is up to 32 characters).
            'sku' => new FieldFilter('sku', [Comparison::StartsWith, Comparison::Equals], options: ['attr' => ['maxlength' => 32]]),
            'priceFrom' => new PriceBound('price'),
            'priceTo' => new PriceBound('price', upper: true),
        ],
        search: ['name', 'sku', 'category.name'],
        sortable: ['name', 'sku', 'category' => 'category.name', 'price', 'stock', 'createdAt'],
        // A whole SKU is that product; anything else word by word.
        sort: ['-createdAt'],
        updateForm: new DoctrineForm(ProductType::class, ['sku_fixed' => true]),
        searchWith: SkuSearch::class,
    ),
    columns: [
        'name',
        'sku',
        'images',
        'category',
        'price' => 'money',
        'stock' => new ComponentFormat('App:StockLevel', ['low' => 5]),
        'active',
        'createdAt',
    ],
    theme: 'crud/product_theme.html.twig',
    section: 'app.nav.catalog',
    role: 'ROLE_ADMIN',
    priority: 10,
    actions: [
        new EditAction(inline: true),
        new CopyAction('copy-sku', 'sku', inline: true),
        // Done on the server, through the guards and RecordChanging / RecordChanged (ToggleProductActive).
        new RunAction('toggle-active', 'toggle-on', ToggleProductActive::class),
        // The add form filled in as a copy of the product (?from=<id>).
        new DuplicateAction(),
    ],
    layout: [
        new Fieldset('basics', ['name', new Row(['sku', 'category']), 'description']),
        new Fieldset('stock', [new Row(['price', 'stock']), 'active']),
        new Fieldset('images', ['images']),
    ],
    // The bundle's deleting asks why (not required): RecordDeleting / RecordDeleted::$input carry it.
    scope: 'admin',
    bulkActions: [new DeactivateProducts(), new BulkDeleteAction(input: new ConfirmInput('app.product.delete_reason', required: false))],
    filtersInView: ['category', 'createdAt'],
    listComponent: 'App:ProductList',
)]
final class ProductCrud
{
}
