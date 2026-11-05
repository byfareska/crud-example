<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Action\BulkResult;
use Byfareska\Crud\Action\RecordActionRunner;
use Byfareska\Crud\Action\RunAction;
use Byfareska\Crud\Resource\CrudFailure;
use Byfareska\Crud\Resource\RecordView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Puts a product on sale or takes it off with a click on its row (RunAction "toggle-active" in ProductCrud), without
 * opening its form. The bundle asks first what the form would: the product is in the scopes of the list, its guards
 * of Update let it be changed, a listener of RecordChanging does not refuse - and RecordChanged tells the audit log.
 */
final readonly class ToggleProductActive implements RecordActionRunner
{
    public function __construct(
        private EntityManagerInterface $entities,
        private TranslatorInterface $translator,
    ) {
    }

    public function run(RunAction $action, RecordView $record, ?string $input): ?BulkResult
    {
        $product = $this->entities->find(Product::class, $record->id) ?? throw CrudFailure::notFound();
        // The rule of the entity (Product::validateActive()): what is out of stock is not for sale.
        if (!$product->active && $product->stock === 0) {
            throw new CrudFailure(422, $this->translator->trans('app.product.active_without_stock', [], 'validators'));
        }

        $product->active = !$product->active;
        $this->entities->flush();

        return null;
    }
}
