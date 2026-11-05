<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use Byfareska\Crud\Attribute\MapCrud;
use Byfareska\Crud\Page\CrudContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A page of the application's own about a record of a resource of the bundle, led to by the RouteAction "summary" of
 * the category. #[MapCrud] gives it the category - after checking that the one asking may see the resource, with a 404
 * page for one that does not exist - and the panel knows what the page is of: its menu, with the categories lit.
 */
final class CategorySummaryController extends AbstractController
{
    #[Route('/admin/categories/{id}/summary', name: 'app_category_summary', methods: ['GET'])]
    public function __invoke(#[MapCrud('category')] CrudContext $category, EntityManagerInterface $entities): Response
    {
        $stats = $entities->createQueryBuilder()
            ->select('COUNT(p.id) AS products', 'COALESCE(SUM(CASE WHEN p.active = true THEN 1 ELSE 0 END), 0) AS active', 'COALESCE(SUM(p.stock), 0) AS stock')
            ->from(Product::class, 'p')
            ->where('IDENTITY(p.category) = :category')
            ->setParameter('category', $category->id)
            ->getQuery()
            ->getSingleResult();

        return $this->render('category/summary.html.twig', ['stats' => array_map(intval(...), $stats)]);
    }
}
