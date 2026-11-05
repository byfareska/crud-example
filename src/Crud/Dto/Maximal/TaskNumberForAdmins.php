<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Resource\CrudDefinitionExtension;
use Byfareska\Crud\Security\Actor;

/**
 * An administrator sees the number of a task in its list, at the front, and in its details; anybody else the
 * columns TaskCrud declares. The declaration stays one for everybody - the routes are made of it - and this changes
 * it for the request.
 */
final readonly class TaskNumberForAdmins implements CrudDefinitionExtension
{
    public function __construct(
        private Actor $actor,
    ) {
    }

    public function resource(): string
    {
        return 'task';
    }

    public function extend(AsCrud $crud): AsCrud
    {
        if (!$this->actor->isGranted('ROLE_ADMIN')) {
            return $crud;
        }

        $number = $crud->column('id');

        return $crud->with(columns: [$number, ...$crud->columns], details: [$number, ...$crud->details]);
    }
}
