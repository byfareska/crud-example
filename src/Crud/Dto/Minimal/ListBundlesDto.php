<?php

declare(strict_types=1);

namespace App\Crud\Dto\Minimal;

use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\MapsToRequest;
use Symfony\Component\Form\Attribute\AsFormType;

/**
 * The list DTO is a form type: its fields would be the filters of the list, and "page", "perPage" and "sort" would
 * be driven by the list. With none the list has no toolbar, and the use case returns every record: the list pages
 * them itself. A use case that needs nothing else may
 * take the DTO itself as its request.
 */
#[AsFormType(options: ['method' => 'GET', 'csrf_protection' => false])]
#[UseCase(ListBundles::class)]
final class ListBundlesDto implements MapsToRequest
{
    public function toRequest(): self
    {
        return $this;
    }
}
