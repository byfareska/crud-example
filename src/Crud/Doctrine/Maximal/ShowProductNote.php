<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\ProductNote;
use Byfareska\Crud\UseCase\Query;
use Doctrine\ORM\EntityManagerInterface;

/**
 * The note of a product, as the section's fields start with it ("note"); empty when it has none.
 */
final readonly class ShowProductNote implements Query
{
    public function __construct(
        private EntityManagerInterface $entities,
    ) {
    }

    public function __invoke(ProductNoteRequest $request): ProductNoteRequest
    {
        $note = $this->entities->find(ProductNote::class, $request->productId);

        return new ProductNoteRequest($request->productId, $note instanceof ProductNote ? $note->note : '');
    }
}
