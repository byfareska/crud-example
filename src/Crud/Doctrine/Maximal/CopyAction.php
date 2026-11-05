<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Action\RecordAction;

/**
 * A kind of record action of the application's own, the bundle has none like it: copies a value of the record to the
 * clipboard - e.g. the SKU of a product - and says so in a toast. Not offered for a record whose value is empty or no
 * text. The button is made by {@see CopyActionHandler}.
 */
final readonly class CopyAction implements RecordAction
{
    /**
     * @param string $name lower-case; part of the translation key
     * @param string $field of the record, e.g. "sku"; the list asks for it even when it is no column
     * @param string $icon a Bootstrap Icons name
     * @param string|null $role only somebody with this role is offered the action
     * @param bool $inline in the list a button on the row instead of an item of the row menu
     */
    public function __construct(
        public string $name,
        public string $field,
        public string $icon = 'copy',
        public ?string $role = null,
        public bool $inline = false,
    ) {
    }

    public function fields(): array
    {
        return [$this->field];
    }

    public function handler(): string
    {
        return CopyActionHandler::class;
    }
}
