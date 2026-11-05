<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Product;
use Byfareska\Crud\Attribute\AsCrudPage;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A page of every product added with #[AsCrudPage]: /admin/products/{id}/stats, with the way to it on the record -
 * in the list a button on the row ("inline") instead of an item of its menu. The bundle makes the route, checks who
 * may see it, answers 404 for a product that does not exist and puts the component into the frame of a page of a
 * record - the component only shows what it is about.
 */
#[AsCrudPage(resource: 'product', name: 'stats', component: 'App:ProductStats', icon: 'graph-up', inline: true)]
#[AsTwigComponent('App:ProductStats', template: 'components/App/ProductStats.html.twig')]
final class ProductStats
{
    /** The id of the record, as in the address. */
    public string $id = '';

    public function __construct(private readonly EntityManagerInterface $entities)
    {
    }

    /** @return array{value: float, days: int, share: float} */
    public function getStats(): array
    {
        // The bundle has already answered 404 for a product that does not exist.
        $product = $this->entities->find(Product::class, (int) $this->id) ?? throw new LogicException('No product ' . $this->id . '.');
        $total = (int) $this->entities->createQueryBuilder()
            ->select('COALESCE(SUM(p.stock), 0)')
            ->from(Product::class, 'p')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'value' => (float) $product->price * $product->stock,
            'days' => (int) $product->createdAt->diff(new DateTimeImmutable())->days,
            'share' => $total === 0 ? 0.0 : $product->stock / $total,
        ];
    }
}
