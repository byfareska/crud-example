<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Resource\CrudFormExtension;
use Byfareska\Crud\Resource\RecordView;
use Byfareska\Crud\Security\Actor;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Only an administrator chooses the priority of a task: anybody else sees it fixed, in the add form and in the edit
 * form - what their browser sends for it is ignored, and so is what the address gives a new task
 * (?with[priority]=high). A form of the request, whatever the driver.
 */
final readonly class PriorityForAdmins implements CrudFormExtension
{
    public function __construct(
        private Actor $actor,
    ) {
    }

    public function resource(): string
    {
        return 'task';
    }

    public function buildForm(AsCrud $crud, FormBuilderInterface $builder, ?RecordView $record): void
    {
        if ($builder->has('priority') && !$this->actor->isGranted('ROLE_ADMIN')) {
            $builder->get('priority')->setDisabled(true);
        }
    }
}
