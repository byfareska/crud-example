<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\InvalidIdException;
use Byfareska\Crud\UseCase\MapsToRequest;

/**
 * Reads the note of a product: what the section of its edit form starts with.
 */
#[UseCase(ShowProductNote::class)]
final class ShowProductNoteDto implements MapsToRequest
{
    public string $id = '';

    public function toRequest(): ProductNoteRequest
    {
        if (!ctype_digit($this->id)) {
            throw new InvalidIdException();
        }

        return new ProductNoteRequest((int) $this->id);
    }
}
