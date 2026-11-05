<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

/**
 * What the use cases of a product's note are asked: the product, and the note to keep (none when it is read).
 */
final readonly class ProductNoteRequest
{
    public function __construct(
        public int $productId,
        public string $note = '',
    ) {
    }
}
