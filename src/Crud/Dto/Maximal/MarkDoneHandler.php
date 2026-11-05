<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use App\Task\Infrastructure\Request\MarkTasksDoneDto;
use Byfareska\Crud\Action\ActionButton;
use Byfareska\Crud\Action\BulkAction;
use Byfareska\Crud\Action\BulkActionHandler;
use Byfareska\Crud\Action\BulkResult;
use Byfareska\Crud\Action\BulkSelection;
use Byfareska\Crud\UseCase\UseCaseExecutor;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The button of {@see MarkDone} and the marking - as everything else about the tasks, through a use case: a DTO run
 * by the UseCaseExecutor.
 */
final readonly class MarkDoneHandler implements BulkActionHandler
{
    public function __construct(
        private UseCaseExecutor $executor,
        private TranslatorInterface $translator,
    ) {
    }

    public function button(BulkAction $action, BulkSelection $selection): ActionButton
    {
        return new ActionButton(
            $action->name,
            $selection->crud->key('bulk.' . $action->name),
            'check2-all',
            attributes: [
                'data-action' => 'live#action',
                'data-live-action-param' => 'bulk',
                'data-live-name-param' => $action->name,
            ],
        );
    }

    public function run(BulkAction $action, BulkSelection $selection): BulkResult
    {
        $dto = new MarkTasksDoneDto();
        $dto->ids = $selection->ids;
        $marked = $this->executor->execute($dto);

        return new BulkResult($this->translator->trans('app.task.marked_done', ['count' => $marked]));
    }
}
