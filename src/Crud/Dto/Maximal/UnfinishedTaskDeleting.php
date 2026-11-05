<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use Byfareska\Crud\Event\RecordDeleting;
use Byfareska\Crud\Resource\CrudFailure;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * A rule the bundle cannot know: a task is finished before it is deleted. Refused by throwing a CrudFailure, whose
 * text the list shows in a toast.
 */
#[AsEventListener]
final readonly class UnfinishedTaskDeleting
{
    public function __invoke(RecordDeleting $event): void
    {
        $record = $event->record;
        if ($record->crud->name === 'task' && ($record->item['done'] ?? false) !== true) {
            throw new CrudFailure(409, 'An unfinished task is not deleted.', translationKey: 'app.task.unfinished');
        }
    }
}
