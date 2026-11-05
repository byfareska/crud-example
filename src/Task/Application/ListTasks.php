<?php

declare(strict_types=1);

namespace App\Task\Application;

use App\Task\Domain\Task;
use App\Task\Domain\Tasks;
use Byfareska\Crud\UseCase\ListPage;
use Byfareska\Crud\UseCase\Query;

final readonly class ListTasks implements Query
{
    public function __construct(private Tasks $tasks)
    {
    }

    public function __invoke(ListTasksRequest $request): ListPage
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($request->query)), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        $tasks = array_filter($this->tasks->all(), static fn (Task $task): bool => ($request->done === null || $task->done === $request->done)
            && ($request->priority === null || $task->priority === $request->priority)
            && array_all($words, static fn (string $word): bool => str_contains(mb_strtolower($task->title), $word)));

        $views = array_map(TaskView::of(...), array_values($tasks));
        usort($views, static function (TaskView $a, TaskView $b) use ($request): int {
            foreach ($request->sort as $sort) {
                $order = $a->{$sort->field} <=> $b->{$sort->field};
                if ($order !== 0) {
                    return $sort->isDescending() ? -$order : $order;
                }
            }

            return $a->id <=> $b->id;
        });

        $page = new ListPage([], count($views), $request->page, $request->perPage);
        $items = array_slice($views, ($page->page - 1) * $page->perPage, $page->perPage);

        return new ListPage(array_map(static fn (TaskView $view): array => $view->toArray(), $items), $page->total, $page->page, $page->perPage);
    }
}
