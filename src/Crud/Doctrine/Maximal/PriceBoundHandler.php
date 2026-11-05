<?php

declare(strict_types=1);

namespace App\Crud\Doctrine\Maximal;

use Byfareska\Crud\Driver\Doctrine\DoctrineFilter;
use Byfareska\Crud\Driver\Doctrine\DoctrineFilterHandler;
use Byfareska\Crud\Driver\Doctrine\DoctrineListQuery;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The field of a {@see PriceBound} - an amount - and what it keeps of the list.
 */
final readonly class PriceBoundHandler implements DoctrineFilterHandler
{
    public function __construct(private FormFactoryInterface $forms)
    {
    }

    public function field(DoctrineFilter $filter, string $name, array $options): FormBuilderInterface
    {
        return $this->forms->createNamedBuilder($name, NumberType::class, null, $options + [
            'scale' => 2,
            'html5' => true,
            'attr' => ['min' => 0, 'step' => '0.01'],
        ]);
    }

    public function apply(DoctrineFilter $filter, DoctrineListQuery $query, string $name, mixed $value): void
    {
        assert($filter instanceof PriceBound);
        $parameter = $query->parameter($name);

        $operator = $filter->upper ? '<=' : '>=';

        $query->query->andWhere(sprintf('%s %s :%s', $query->path($filter->field), $operator, $parameter))
            ->setParameter($parameter, $value);
    }
}
