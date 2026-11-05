<?php

declare(strict_types=1);

namespace App\Crud\Dto\Maximal;

use App\Task\Infrastructure\Request\CreateTaskDto;
use App\Task\Infrastructure\Request\DeleteTaskDto;
use App\Task\Infrastructure\Request\ListTasksDto;
use App\Task\Infrastructure\Request\ShowTaskDto;
use App\Task\Infrastructure\Request\UpdateTaskDto;
use Byfareska\Crud\Action\BulkDeleteAction;
use Byfareska\Crud\Action\DeleteAction;
use Byfareska\Crud\Action\DuplicateAction;
use Byfareska\Crud\Action\EditAction;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Layout\Column;
use Byfareska\Crud\Layout\Row;

/**
 * The dto driver with everything: every operation is a DTO naming its use case - the tasks are kept in a JSON file
 * (App\Task\Infrastructure\JsonFileTasks), the panel only runs the use cases. The filters, the order and the pages of
 * the list are fields of ListTasksDto. Besides: search, the sizes of a page of the list, the details without the id,
 * a layout of the form and another of the details, a theme for the priority, the actions on the row, bulk actions -
 * the bundle's deleting and one of the application's own, run through a use case too (MarkDone) - a done task no
 * longer edited (DoneTaskGuard), an unfinished one not deleted (UnfinishedTaskDeleting) and deleting only for an
 * administrator (roles) - everybody else lists, reads, adds and edits.
 *
 * What can be edited are the fields of the create / update DTOs (CreateTaskDto, UpdateTaskDto). A rule of the domain
 * is the use case's to refuse, and the DTO says at which field: a new task is not added done (DoneTaskAdded).
 */
#[AsCrud(
    name: 'task',
    path: '/tasks',
    icon: 'check2-square',
    list: ListTasksDto::class,
    read: ShowTaskDto::class,
    create: CreateTaskDto::class,
    update: UpdateTaskDto::class,
    delete: DeleteTaskDto::class,
    columns: ['title', 'priority', 'done', 'createdAt'],
    details: ['title', 'priority', 'done', 'createdAt', 'attachments'],
    theme: 'crud/task_theme.html.twig',
    search: 'query',
    section: 'app.nav.work',
    actions: [new EditAction(inline: true), new DeleteAction(inline: true), new DuplicateAction()],
    formLayout: ['title', new Row(['priority', 'done']), 'attachments'],
    detailsLayout: [new Column(8, ['title', 'createdAt', 'attachments']), new Column(4, ['priority', 'done'])],
    bulkActions: [new MarkDone(), new BulkDeleteAction()],
    perPage: [5, 10, 25, 50],
    defaultPerPage: 10,
    roles: ['delete' => 'ROLE_ADMIN'],
)]
final class TaskCrud
{
}
