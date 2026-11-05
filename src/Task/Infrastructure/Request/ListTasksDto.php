<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Request;

use App\Task\Application\ListTasks;
use App\Task\Application\ListTasksRequest;
use App\Task\Domain\Priority;
use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\UseCase\MapsToRequest;
use Byfareska\Crud\UseCase\Sort;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The list of tasks. Its fields are the filters of the list page ("query", "done", "priority"); "sort", "page" and
 * "perPage" are driven by the list itself. The same DTO could be the input of an API endpoint or a console command.
 */
#[AsFormType(options: ['method' => 'GET', 'csrf_protection' => false])]
#[UseCase(ListTasks::class)]
final class ListTasksDto implements MapsToRequest
{
    public const array SORTS = ['title', '-title', 'priority', '-priority', 'createdAt', '-createdAt'];

    #[FormField(SearchType::class, ['label' => 'crud.task.field.query.name', 'required' => false, 'empty_data' => ''])]
    #[Assert\Length(max: 80)]
    public string $query = '';

    #[FormField(ChoiceType::class, [
        'label' => 'crud.task.field.done.name',
        'choices' => ['crud.filter.yes' => true, 'crud.filter.no' => false],
        'placeholder' => 'crud.filter.any',
        'required' => false,
    ])]
    public ?bool $done = null;

    #[FormField(ChoiceType::class, [
        'label' => 'crud.task.field.priority.name',
        'choices' => CreateTaskDto::PRIORITIES,
        'placeholder' => 'crud.filter.any',
        'required' => false,
    ])]
    #[Assert\Choice(choices: CreateTaskDto::PRIORITIES)]
    public ?string $priority = null;

    /** @var list<string> */
    #[FormField(ChoiceType::class, ['choices' => self::SORTS, 'multiple' => true, 'required' => false, 'empty_data' => ['-createdAt']])]
    #[Assert\Choice(choices: self::SORTS, multiple: true)]
    public array $sort = ['-createdAt'];

    #[FormField(IntegerType::class, ['required' => false, 'empty_data' => '1'])]
    #[Assert\Positive]
    public int $page = 1;

    /** One of #[AsCrud] perPage (App\Crud\Dto\Maximal\TaskCrud): the list asks for no other. */
    #[FormField(IntegerType::class, ['required' => false, 'empty_data' => '10'])]
    #[Assert\Range(min: 1, max: 100)]
    public int $perPage = 10;

    public function toRequest(): ListTasksRequest
    {
        return new ListTasksRequest($this->query, $this->done, $this->priority === null ? null : Priority::from($this->priority), Sort::parseAll($this->sort), $this->page, $this->perPage);
    }
}
