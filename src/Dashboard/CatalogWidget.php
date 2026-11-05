<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Entity\Product;
use Byfareska\Crud\Attribute\AsDashboardWidget;
use Byfareska\Crud\Dashboard\DashboardWidget;
use Byfareska\Crud\Dashboard\DashboardWidgetProps;
use Byfareska\Crud\Dashboard\WidgetSize;
use Byfareska\Crud\Panel\ScopedComponent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * The catalogue at a glance, on the admin panel's dashboard only ("boards"). #[AsDashboardWidget] is all it takes: the
 * bundle finds it by autoconfiguration.
 */
#[AsDashboardWidget(
    key: 'app.catalog',
    title: 'app.widget.catalog.title',
    description: 'app.widget.catalog.description',
    icon: 'box-seam',
    accent: '#22c55e',
    category: DashboardWidget::CATEGORY_SALES,
    size: new WidgetSize(3, 2),
    maxSize: new WidgetSize(6, 2),
    role: 'ROLE_ADMIN',
    default: true,
    priority: 5,
    lazy: false,
    boards: ['admin'],
)]
#[AsTwigComponent('App:CatalogWidget', template: 'components/App/CatalogWidget.html.twig')]
final class CatalogWidget implements ScopedComponent
{
    use DashboardWidgetProps;

    public function __construct(private readonly EntityManagerInterface $entities)
    {
    }

    /** @return array{products: int, active: int, lowStock: int} */
    public function getStats(): array
    {
        $row = $this->entities->createQueryBuilder()
            ->select('COUNT(p.id) AS products', 'SUM(CASE WHEN p.active = true THEN 1 ELSE 0 END) AS active', 'SUM(CASE WHEN p.stock < 5 THEN 1 ELSE 0 END) AS lowStock')
            ->from(Product::class, 'p')
            ->getQuery()
            ->getSingleResult();

        return array_map(intval(...), $row);
    }
}
