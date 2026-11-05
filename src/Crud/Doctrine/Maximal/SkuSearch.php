<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineQuery;
use Byfareska\Crud\Driver\Doctrine\DoctrineSearch;
use Byfareska\Crud\Driver\Doctrine\WordsSearch;

/**
 * The search of the products (ProductCrud: searchWith): a whole SKU typed ("kub-001") is that product and no other -
 * not every product whose name mentions it; anything else is looked for word by word, as the bundle does.
 */
final readonly class SkuSearch implements DoctrineSearch
{
    private const string SKU = '/^[A-Za-z]{3}-\d{3}$/';

    public function __construct(
        private WordsSearch $words,
    ) {
    }

    public function apply(DoctrineQuery $query, array $fields, string $text): void
    {
        if (preg_match(self::SKU, $text) !== 1) {
            $this->words->apply($query, $fields, $text);

            return;
        }

        $sku = $query->parameter('sku');
        $query->query->andWhere(sprintf('%s = :%s', $query->path('sku'), $sku))->setParameter($sku, strtoupper($text));
    }
}
