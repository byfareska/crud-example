<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use App\Entity\Category;
use Byfareska\Crud\Attribute\AsCrudPage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * A page of the whole resource, not of a record (#[AsCrudPage(collection: true)]): /admin/categories/overview, led to
 * by a button next to "new" on the list of categories, for those who may see the list.
 */
#[AsCrudPage(resource: 'category', name: 'overview', component: 'App:CategoryOverview', icon: 'columns-gap', collection: true)]
#[AsTwigComponent('App:CategoryOverview', template: 'components/App/CategoryOverview.html.twig')]
final class CategoryOverview
{
    public function __construct(private readonly EntityManagerInterface $entities)
    {
    }

    /** @return array<string, int> the number of products by category */
    public function getCounts(): array
    {
        $counts = [];
        foreach ($this->entities->getRepository(Category::class)->findBy([], ['name' => 'ASC']) as $category) {
            $counts[$category->name] = $category->getProductCount();
        }

        return $counts;
    }
}
