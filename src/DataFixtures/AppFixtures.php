<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Supplier;
use App\Task\Application\SaveTask;
use App\Task\Application\SaveTaskRequest;
use App\Task\Domain\Priority;
use App\Task\Domain\Tasks;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Demo data: a small catalogue and suppliers (doctrine driver) and a few tasks (dto driver, through its own use case).
 */
final class AppFixtures extends Fixture
{
    private const array CATALOGUE = [
        'Mugs' => [['Ceramic mug', 'MUG-001', '29.90', 42], ['Travel mug', 'MUG-002', '79.00', 3], ['Enamel mug', 'MUG-003', '39.50', 17]],
        'Tea' => [['Earl Grey 100 g', 'TEA-001', '24.00', 60], ['Sencha 50 g', 'TEA-002', '32.00', 0], ['Rooibos 100 g', 'TEA-003', '21.50', 25]],
        'Coffee' => [['Espresso 1 kg', 'COF-001', '89.00', 12], ['Colombia 250 g', 'COF-002', '45.00', 4], ['Ethiopia 250 g', 'COF-003', '52.00', 9]],
        'Accessories' => [['Hand grinder', 'ACC-001', '149.00', 6], ['V60 dripper', 'ACC-002', '69.00', 14], ['Paper filters', 'ACC-003', '15.00', 120]],
    ];

    private const array SUPPLIERS = [
        ['Northfield Ceramics', 'orders@ceramics.example'],
        ['Tea House Wholesale', 'office@teahouse.example'],
        ['Green Bean Roastery', null],
    ];

    public function __construct(
        private readonly SaveTask $saveTask,
        private readonly Tasks $tasks,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $day = 0;
        foreach (self::CATALOGUE as $name => $products) {
            $category = new Category();
            $category->name = $name;
            $manager->persist($category);
            foreach ($products as [$title, $sku, $price, $stock]) {
                $product = new Product();
                $product->name = $title;
                $product->sku = $sku;
                $product->category = $category;
                $product->price = $price;
                $product->stock = $stock;
                $product->active = $stock > 0;
                $product->createdAt = new DateTimeImmutable(sprintf('-%d days', $day++ * 3));
                $manager->persist($product);
            }
        }
        foreach (self::SUPPLIERS as [$name, $email]) {
            $supplier = new Supplier();
            $supplier->name = $name;
            $supplier->email = $email;
            $manager->persist($supplier);
        }
        $manager->flush();

        // The tasks are no rows of the database: the purge does not clear them.
        foreach ($this->tasks->all() as $task) {
            $this->tasks->remove($task->id);
        }
        foreach ([
            ['Order travel mugs', Priority::High, false],
            ['Photograph the new teas', Priority::Normal, false],
            ['Describe the hand grinder', Priority::Low, false],
            ['Set up a coffee promotion', Priority::Normal, true],
        ] as [$title, $priority, $done]) {
            // A task is added not done yet; then it is done.
            $task = ($this->saveTask)(new SaveTaskRequest(null, $title, $priority, false));
            if ($done) {
                ($this->saveTask)(new SaveTaskRequest($task->id, $title, $priority, true));
            }
        }
    }
}
