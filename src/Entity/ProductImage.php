<?php

declare(strict_types=1);

namespace App\Entity;

use Byfareska\Crud\Files\FileItem;
use Byfareska\Crud\Files\FileReference;
use Byfareska\Crud\Files\ProvidesFile;
use Doctrine\ORM\Mapping as ORM;

/**
 * An image of the gallery of a product: its file, its place in the gallery and its alternative text, as columns. The
 * field of the gallery (ProductType: binding "items") adds, removes and orders them; the list shows them as images
 * (ProvidesFile).
 */
#[ORM\Entity]
class ProductImage implements ProvidesFile
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public ?Product $product = null;

    /** A reference: "public://ab/cd/....jpg". */
    #[ORM\Column(length: 255)]
    public string $file = '';

    #[ORM\Column]
    public int $position = 0;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $alt = null;

    public function fileItem(): FileItem
    {
        return new FileItem(FileReference::parse($this->file), ['alt' => $this->alt]);
    }
}
