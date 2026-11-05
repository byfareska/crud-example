<?php

declare(strict_types=1);

namespace App\Crud\Dto\Minimal;

use Byfareska\Crud\UseCase\ListPage;
use Byfareska\Crud\UseCase\Query;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A use case is an invokable Query (or Command) service; the list one returns a Page of arrays, keyed by the columns.
 */
final readonly class ListBundles implements Query
{
    /**
     * @param array<string, array{path: string, namespace: string}> $bundles
     */
    public function __construct(
        #[Autowire('%kernel.bundles_metadata%')]
        private array $bundles,
    ) {
    }

    public function __invoke(ListBundlesDto $request): ListPage
    {
        $items = [];
        foreach ($this->bundles as $name => $bundle) {
            $items[] = ['name' => $name, 'namespace' => $bundle['namespace']];
        }

        return new ListPage($items, count($items), 1, max(1, count($items)));
    }
}
