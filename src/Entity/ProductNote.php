<?php

declare(strict_types=1);

namespace App\Entity;

use App\Crud\Doctrine\Maximal\ProductNoteSection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A note about a product, kept by another part of the application than the product itself - its CRUD does not know
 * it. It comes into the product's forms as a section: {@see ProductNoteSection}.
 */
#[ORM\Entity]
class ProductNote
{
    public function __construct(
        #[ORM\Id, ORM\Column]
        public int $productId,
        #[ORM\Column(type: 'text')]
        public string $note = '',
    ) {
    }
}
