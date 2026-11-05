<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Minimal;

use App\Entity\Supplier;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Driver\Doctrine\DoctrineSource;

/**
 * The doctrine driver at its simplest: an entity and the columns of its list. Everything else is a default - the
 * list, details, add, edit and delete, the form guessed from the mapping, ordered by the id, in the panel "panel" for
 * everybody signed in. "section" only puts the list into the menu.
 *
 * The attribute may as well be put on the entity itself, with `new DoctrineSource(self::class)`.
 *
 * Texts: crud.supplier.title, .new, .edit and crud.supplier.field.<field>.name.
 */
#[AsCrud(
    name: 'supplier',
    path: '/suppliers',
    icon: 'truck',
    source: new DoctrineSource(Supplier::class),
    columns: ['name', 'logo', 'email'],
    section: 'app.nav.minimal',
)]
final class SupplierCrud
{
}
