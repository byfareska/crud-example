<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Resource\RecordRow;
use Byfareska\Crud\Resource\RecordView;

/**
 * Dims the row of a product that is not for sale (both themes know "opacity-50").
 */
final readonly class InactiveProductRow implements RecordRow
{
    public function resource(): string
    {
        return 'product';
    }

    public function fields(): array
    {
        return ['active'];
    }

    public function attributes(RecordView $record): array
    {
        return ($record->item['active'] ?? true) === false ? ['class' => 'opacity-50', 'data-inactive' => true] : [];
    }
}
