<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Action\ActionButton;
use Byfareska\Crud\Action\BulkAction;
use Byfareska\Crud\Action\BulkActionHandler;
use Byfareska\Crud\Action\BulkResult;
use Byfareska\Crud\Action\BulkSelection;
use Byfareska\Crud\Resource\CrudFailure;
use Byfareska\Crud\Resource\RecordChanges;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The button of {@see DeactivateProducts} and the taking off sale - each product through RecordChanges: its guards of
 * Update asked, RecordChanging and RecordChanged heard (an audit log sees it), what is refused left as it is.
 */
final readonly class DeactivateProductsHandler implements BulkActionHandler
{
    public function __construct(
        private EntityManagerInterface $entities,
        private TranslatorInterface $translator,
        private RecordChanges $changes,
    ) {
    }

    public function button(BulkAction $action, BulkSelection $selection): ActionButton
    {
        return new ActionButton(
            $action->name,
            $selection->crud->key('bulk.' . $action->name),
            'eye-slash',
            attributes: [
                'data-action' => 'live#action',
                'data-live-action-param' => 'bulk',
                'data-live-name-param' => $action->name,
            ],
        );
    }

    public function run(BulkAction $action, BulkSelection $selection): BulkResult
    {
        $done = 0;
        foreach ($selection->each() as $id) {
            try {
                $this->changes->change($selection->crud, $id, $action->name, function () use ($id): void {
                    $product = $this->entities->find(Product::class, $id);
                    if ($product instanceof Product) {
                        $product->active = false;
                        $this->entities->flush();
                    }
                });
                ++$done;
            } catch (CrudFailure) {
                // Not in the scopes any more, kept by a guard or a listener: left as it is.
            }
        }

        return new BulkResult($this->translator->trans('app.deactivated', ['count' => $done]));
    }
}
