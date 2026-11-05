<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineQuery;
use Byfareska\Crud\Driver\Doctrine\DoctrineScope;
use Psr\Clock\ClockInterface;

/**
 * A product added with a date ahead (to go on sale later) is not in the panel until then: not listed, not found by
 * its address, not offered as a choice. Who may see what is a scope like this one, e.g. "only my records":
 * `$query->query->andWhere('e.owner = :me')` with the user of Security.
 */
final readonly class ProductsAddedSoFar implements DoctrineScope
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function resource(): string
    {
        return 'product';
    }

    public function apply(DoctrineQuery $query): void
    {
        $now = $query->parameter('now');
        $query->query->andWhere(sprintf('%s <= :%s', $query->path('createdAt'), $now))
            ->setParameter($now, $this->clock->now());
    }
}
