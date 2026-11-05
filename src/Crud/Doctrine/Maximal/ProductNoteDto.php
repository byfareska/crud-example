<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\Failure;
use Byfareska\Crud\UseCase\InvalidIdException;
use Byfareska\Crud\UseCase\MapsToRequest;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The fields of the note section, in the add and the edit form of a product; the id of the product is put by the
 * panel (of a new one once it is saved).
 */
#[AsFormType]
#[UseCase(SaveProductNote::class, errors: [NoteWithALink::class => new Failure(422, field: 'note', message: 'app.product.note.no_links')])]
final class ProductNoteDto implements MapsToRequest
{
    public string $id = '';

    #[FormField(TextareaType::class, ['label' => 'app.product.note.field', 'required' => false, 'empty_data' => ''])]
    #[Assert\Length(max: 500)]
    public string $note = '';

    public function toRequest(): ProductNoteRequest
    {
        if (!ctype_digit($this->id)) {
            throw new InvalidIdException();
        }

        return new ProductNoteRequest((int) $this->id, $this->note);
    }
}
