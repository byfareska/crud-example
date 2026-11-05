<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Action\ActionButton;
use Byfareska\Crud\Action\RecordAction;
use Byfareska\Crud\Action\RecordActionHandler;
use Byfareska\Crud\Resource\RecordView;
use InvalidArgumentException;
use Stringable;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The button of a {@see CopyAction}: the "clipboard" Stimulus controller of the application
 * (assets/controllers/clipboard_controller.js), given the value and the texts of its toasts. A service like any other:
 * autoconfigured, the bundle finds it by its class.
 */
final readonly class CopyActionHandler implements RecordActionHandler
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function button(RecordAction $action, RecordView $record): ?ActionButton
    {
        if (!$action instanceof CopyAction) {
            throw new InvalidArgumentException(sprintf('%s makes the buttons of a %s.', self::class, CopyAction::class));
        }

        $value = $record->item[$action->field] ?? null;
        $text = is_scalar($value) || $value instanceof Stringable ? trim((string) $value) : '';
        if ($text === '') {
            return null;
        }

        return new ActionButton(
            $action->name,
            $record->crud->key('action.' . $action->name),
            $action->icon,
            'button',
            [
                'type' => 'button',
                'data-controller' => 'clipboard',
                'data-action' => 'clipboard#copy',
                'data-clipboard-text-value' => $text,
                'data-clipboard-done-value' => $this->translator->trans('app.copy.done'),
                'data-clipboard-failed-value' => $this->translator->trans('app.copy.failed'),
            ],
            $action->inline,
        );
    }
}
