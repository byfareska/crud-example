<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Attribute\AsCrudFormSection;

/**
 * The note of a product (App\Entity\ProductNote) as a section of the product's forms - the edit form and, with
 * "create", the add form: saved by its own use case (SaveProductNote) right after the product, in the same transaction.
 * A note the use case refuses (a link in it) leaves the new product unsaved as well.
 */
#[AsCrudFormSection(
    resource: 'product',
    name: 'note',
    label: 'app.product.note.label',
    update: ProductNoteDto::class,
    read: ShowProductNoteDto::class,
    create: ProductNoteDto::class,
)]
final class ProductNoteSection
{
}
