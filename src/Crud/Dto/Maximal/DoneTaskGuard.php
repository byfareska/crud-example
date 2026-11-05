<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use Byfareska\Crud\Resource\CrudOperation;
use Byfareska\Crud\Resource\RecordGuard;
use Byfareska\Crud\Resource\RecordView;

/**
 * Who may do what with one record: a task that is done is no longer edited - its edit button goes away, and its edit
 * form answers 403. A service like any other: autoconfigured.
 */
final readonly class DoneTaskGuard implements RecordGuard
{
    public function resource(): string
    {
        return 'task';
    }

    public function fields(): array
    {
        return ['done'];
    }

    public function allows(CrudOperation $operation, RecordView $record): bool
    {
        return $operation !== CrudOperation::Update || ($record->item['done'] ?? false) !== true;
    }
}
