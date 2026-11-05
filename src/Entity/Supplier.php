<?php

declare(strict_types=1);

namespace App\Entity;

use App\Crud\Doctrine\Minimal\SupplierCrud;
use Byfareska\Crud\Attribute\CrudFile;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A plain entity: made with no arguments, its constraints are what the form checks. Its CRUD: {@see SupplierCrud}.
 */
#[ORM\Entity]
class Supplier
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank, Assert\Length(max: 120)]
    public string $name = '';

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    public ?string $email = null;

    /**
     * An image uploaded or picked from the library: its reference ("public://ab/cd/....png") in a column. The attribute
     * is all the guessed form needs to make it a field of a file; the list shows it as an image.
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[CrudFile(accept: ['images'])]
    public ?string $logo = null;
}
