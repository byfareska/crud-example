<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Resource\CrudSaveButton;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * A button of the edit form of a category of the application's own, next to "save": it saves, then shows the
 * products of the category (CategoryProductsCrud). RecordSaved knows it saved ($then = "products").
 */
final readonly class SaveAndSeeProducts implements CrudSaveButton
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
    }

    public function resource(): string
    {
        return 'category';
    }

    public function name(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return 'app.category.save_products';
    }

    public function isFor(AsCrud $crud, ?string $id): bool
    {
        // A category being added has no products yet.
        return $id !== null;
    }

    public function afterSave(AsCrud $crud, ?string $id, bool $created): string
    {
        return $this->urls->generate('crud_category_product_list', ['category' => $id]);
    }
}
