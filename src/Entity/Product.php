<?php

declare(strict_types=1);

namespace App\Entity;

use App\Crud\Doctrine\Maximal\ProductCrud;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Its CRUD is declared apart, on {@see ProductCrud}: the entity knows nothing of the panel. Its rules are its own
 * constraints, so they hold wherever it is saved - the panel's form shows them at their field (validateActive()).
 */
#[ORM\Entity]
#[UniqueEntity('sku')]
class Product
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank, Assert\Length(max: 120)]
    public string $name = '';

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank, Assert\Regex('/^[A-Z0-9-]+$/', message: 'app.product.sku_format')]
    public string $sku = '';

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(onDelete: 'RESTRICT')]
    public ?Category $category = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    public string $price = '0.00';

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    public int $stock = 0;

    /** For sale; not without stock (validateActive()). */
    #[ORM\Column]
    public bool $active = false;

    /** HTML of the editor (ProductType), with images uploaded into it. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $description = null;

    #[ORM\Column]
    public DateTimeImmutable $createdAt;

    /**
     * The gallery, in its order: the field of files (ProductType) goes through addImage() and removeImage().
     *
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->images = new ArrayCollection();
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): void
    {
        $image->product = $this;
        $this->images->add($image);
    }

    public function removeImage(ProductImage $image): void
    {
        $this->images->removeElement($image);
    }

    /**
     * A rule of two fields: what is out of stock is not for sale. The error goes to "active", where the form shows it.
     */
    #[Assert\Callback]
    public function validateActive(ExecutionContextInterface $context): void
    {
        if ($this->active && $this->stock === 0) {
            $context->buildViolation('app.product.active_without_stock')
                ->atPath('active')
                ->addViolation();
        }
    }
}
