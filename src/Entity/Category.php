<?php

declare(strict_types=1);

namespace App\Entity;

use App\Crud\Doctrine\Maximal\CategoryCrud;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Its CRUD is declared apart, on {@see CategoryCrud}.
 */
#[ORM\Entity]
#[UniqueEntity('name')]
class Category implements Stringable
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank, Assert\Length(max: 80)]
    public string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $description = null;

    /** Optimistic locking: the edit form refuses to save over a change made since it was opened. */
    #[ORM\Column(type: Types::INTEGER), ORM\Version]
    public int $version = 1;

    /** @var Collection<int, Product> counted without being read (EXTRA_LAZY) */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'category', fetch: 'EXTRA_LAZY')]
    public Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /** What the column "productCount" of the list prints. */
    public function getProductCount(): int
    {
        return $this->products->count();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
