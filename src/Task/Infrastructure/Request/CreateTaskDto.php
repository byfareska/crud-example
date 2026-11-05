<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Request;

use App\Task\Application\SaveTask;
use App\Task\Application\SaveTaskRequest;
use App\Task\Domain\DoneTaskAdded;
use App\Task\Domain\Priority;
use Byfareska\Crud\Attribute\UseCase;
use Byfareska\Crud\Files\Form\CrudFileType;
use Byfareska\Crud\UseCase\Failure;
use Byfareska\Crud\UseCase\MapsToRequest;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The form of a new task is the DTO itself (#[AsFormType]): its fields, labels and constraints are here. What the use
 * case refuses is "errors": DoneTaskAdded, a rule of the domain, is shown at the field "done" like a constraint.
 */
#[AsFormType]
#[UseCase(SaveTask::class, errors: [DoneTaskAdded::class => new Failure(422, field: 'done', message: 'app.task.added_done')])]
class CreateTaskDto implements MapsToRequest
{
    public const array PRIORITIES = ['crud.task.priority.low' => 'low', 'crud.task.priority.normal' => 'normal', 'crud.task.priority.high' => 'high'];

    #[FormField(TextType::class, ['label' => 'crud.task.field.title.name', 'empty_data' => ''])]
    #[Assert\NotBlank, Assert\Length(max: 160)]
    public string $title = '';

    #[FormField(ChoiceType::class, ['label' => 'crud.task.field.priority.name', 'choices' => self::PRIORITIES])]
    #[Assert\Choice(choices: self::PRIORITIES)]
    public string $priority = 'normal';

    #[FormField(CheckboxType::class, ['label' => 'crud.task.field.done.name', 'required' => false])]
    public bool $done = false;

    /**
     * Files of the task in the private storage (opened only through the panel's signed addresses), kept as their
     * references in the JSON file of the tasks - a field of files works without a database.
     *
     * @var list<string>
     */
    #[FormField(CrudFileType::class, [
        'label' => 'crud.task.field.attachments.name',
        'storage' => 'private',
        'multiple' => true,
        'max_files' => 5,
        'required' => false,
    ])]
    public array $attachments = [];

    public function toRequest(): SaveTaskRequest
    {
        return new SaveTaskRequest(null, $this->title, Priority::from($this->priority), $this->done, $this->attachments);
    }
}
