<?php

declare(strict_types=1);

namespace App\Tests\Fixtures;

use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Resource\ListDefaults;

/**
 * What the list of suppliers starts with in the tests: from Z to A - as a ListDefaults of an application would start
 * it with what the one looking chose last.
 */
final readonly class SuppliersByNameDescending implements ListDefaults
{
    public function resource(): string
    {
        return 'supplier';
    }

    public function filters(AsCrud $crud): array
    {
        return [];
    }

    public function sort(AsCrud $crud): ?string
    {
        return '-name';
    }
}
