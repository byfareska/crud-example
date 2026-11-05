<?php

declare(strict_types=1);

namespace App\Dashboard;

use App\Task\Domain\Task;
use App\Task\Domain\Tasks;
use Byfareska\Crud\Attribute\AsDashboardWidget;
use Byfareska\Crud\Dashboard\DashboardWidgetProps;
use Byfareska\Crud\Dashboard\WidgetSize;
use Byfareska\Crud\Panel\ScopedComponent;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsDashboardWidget(
    key: 'app.tasks',
    title: 'app.widget.tasks.title',
    description: 'app.widget.tasks.description',
    icon: 'check2-square',
    accent: '#8b5cf6',
    size: new WidgetSize(3, 2),
    default: true,
    priority: 5,
    lazy: false,
    boards: ['panel'],
)]
#[AsTwigComponent('App:TasksWidget', template: 'components/App/TasksWidget.html.twig')]
final class TasksWidget implements ScopedComponent
{
    use DashboardWidgetProps;

    public function __construct(private readonly Tasks $tasks)
    {
    }

    /** @return list<Task> the open ones, the most urgent first */
    public function getOpen(): array
    {
        $open = array_values(array_filter($this->tasks->all(), static fn (Task $task): bool => !$task->done));
        $rank = ['high' => 0, 'normal' => 1, 'low' => 2];
        usort($open, static fn (Task $a, Task $b): int => [$rank[$a->priority->value], $a->createdAt] <=> [$rank[$b->priority->value], $b->createdAt]);

        return $open;
    }
}
