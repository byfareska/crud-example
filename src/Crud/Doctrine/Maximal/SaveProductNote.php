<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\ProductNote;
use Byfareska\Crud\UseCase\Command;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Keeps the note of a product; an empty one is none.
 */
final readonly class SaveProductNote implements Command
{
    public function __construct(
        private EntityManagerInterface $entities,
    ) {
    }

    public function __invoke(ProductNoteRequest $request): void
    {
        if (preg_match('~https?://~i', $request->note) === 1) {
            throw new NoteWithALink();
        }

        $note = $this->entities->find(ProductNote::class, $request->productId);
        if (trim($request->note) === '') {
            if ($note instanceof ProductNote) {
                $this->entities->remove($note);
            }
        } elseif ($note instanceof ProductNote) {
            $note->note = $request->note;
        } else {
            $this->entities->persist(new ProductNote($request->productId, $request->note));
        }
        $this->entities->flush();
    }
}
