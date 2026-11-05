<?php

declare(strict_types=1);

namespace App\Crud\Dto\Minimal;

use Byfareska\Crud\Attribute\AsCrud;

/**
 * The dto driver at its simplest: only a list - one DTO ({@see ListBundlesDto}) naming its use case
 * ({@see ListBundles}), which returns the records. With no read, create, update nor delete the resource has no other
 * pages and its rows lead nowhere. The records are no rows of a database: the bundles of the kernel.
 *
 * Texts: crud.bundle.title and crud.bundle.field.<field>.name.
 */
#[AsCrud(
    name: 'bundle',
    path: '/bundles',
    icon: 'boxes',
    list: ListBundlesDto::class,
    columns: ['name', 'namespace'],
    section: 'app.nav.minimal',
)]
final class BundleCrud
{
}
