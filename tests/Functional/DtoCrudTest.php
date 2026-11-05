<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Task\Domain\Task;
use App\Task\Domain\Tasks;
use Byfareska\Crud\Event\RecordSaving;
use Byfareska\Crud\Resource\CrudFailure;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The "dto" driver: the same pages, run through the use cases of the tasks (kept in a JSON file).
 */
final class DtoCrudTest extends PanelTestCase
{
    public function testListSearchesAndFilters(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());
        self::assertCount(4, $list->render()->crawler()->filter('tbody tr[id]'));

        $list->set('filters', ['query' => 'mugs']);
        self::assertCount(1, $list->render()->crawler()->filter('tbody tr[id]'));

        $list->set('filters', ['done' => '1']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('promotion', $rows->text());
    }

    public function testAnExtensionShowsTheNumberToAnAdministratorOnly(): void
    {
        $asUser = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());
        $userHeadings = $asUser->render()->crawler()->filter('thead th')->count();

        $asAdmin = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::admin());
        $crawler = $asAdmin->render()->crawler();
        self::assertCount($userHeadings + 1, $crawler->filter('thead th'));
        // The id, at the front of every row.
        $task = static::getContainer()->get(Tasks::class)->all()[0] ?? self::fail('No task.');
        self::assertStringContainsString($task->id, $crawler->filter('tbody tr[id="task-' . $task->id . '"]')->text(''));
    }

    public function testAFilterOfTheDtoAndTheSizesOfAPage(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());
        $crawler = $list->render()->crawler();
        // Those of the resource (TaskCrud: perPage), shown at first its defaultPerPage.
        self::assertSame(['5', '10', '25', '50'], $crawler->filter('select[data-live-set-model-value="perPage"] option')->extract(['value']));
        self::assertSame('10', $crawler->filter('select[data-live-set-model-value="perPage"] option[selected]')->attr('value'));

        $list->set('filters', ['priority' => 'normal']);
        self::assertCount(2, $list->render()->crawler()->filter('tbody tr[id]'));
    }

    public function testABulkActionOfTheApplicationRunsAUseCase(): void
    {
        $open = array_values(array_filter($this->tasks()->all(), static fn (Task $task): bool => !$task->done));
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());

        $list->set('selected', [$open[0]->id, $open[1]->id]);
        // Deleting is for an administrator (roles).
        self::assertSame(['done'], $list->render()->crawler()->filter('[data-live-action-param="bulk"]')->extract(['data-live-name-param']));
        $list->call('bulk', ['name' => 'done']);

        self::assertTrue($this->tasks()->get($open[0]->id)->done);
        self::assertTrue($this->tasks()->get($open[1]->id)->done);
        self::assertFalse($this->tasks()->get($open[2]->id)->done);
    }

    public function testCreateEditAndDelete(): void
    {
        // An administrator: the priority is theirs to choose (PriorityForAdmins).
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'task'], $this->client)->actingAs(self::admin());
        self::assertStringContainsString('create_task_dto.title', self::submitInvalid($form, ['create_task_dto' => ['title' => '', 'priority' => 'high']]));

        $form->submitForm(['create_task_dto' => ['title' => 'Nowe zadanie z testu', 'priority' => 'high']], 'save');
        $task = $this->find('Nowe zadanie z testu');
        self::assertNotNull($task);
        self::assertSame('high', $task->priority->value);

        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'id' => $task->id], $this->client)->actingAs(self::user());
        self::assertStringContainsString('Nowe zadanie z testu', $edit->render()->toString());
        $edit->submitForm(['update_task_dto' => ['title' => 'Zmienione', 'priority' => 'low', 'done' => '1']], 'save');
        $changed = $this->tasks()->get($task->id);
        self::assertSame('Zmienione', $changed->title);
        self::assertTrue($changed->done);

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::admin());
        $list->call('delete', ['id' => $task->id]);
        self::assertNull($this->find('Zmienione'));
    }

    public function testOnlyAnAdministratorChangesThePriorityOfATask(): void
    {
        // Neither what the browser sends nor what the address gives a new task: the field is disabled.
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'with' => ['priority' => 'high']], $this->client)->actingAs(self::user());
        self::assertCount(1, $form->render()->crawler()->filter('select[name="create_task_dto[priority]"][disabled]'));
        $form->submitForm(['create_task_dto' => ['title' => 'Priorytet z testu', 'priority' => 'low']], 'save');
        $task = $this->find('Priorytet z testu');
        self::assertNotNull($task);
        self::assertSame('normal', $task->priority->value, 'Added with the default priority.');

        $admin = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'with' => ['priority' => 'high']], $this->client)->actingAs(self::admin());
        $admin->submitForm(['create_task_dto' => ['title' => 'Priorytet admina']], 'save');
        self::assertSame('high', $this->find('Priorytet admina')?->priority->value, 'The address fills in a field one may change.');

        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'id' => $task->id], $this->client)->actingAs(self::user());
        self::assertCount(1, $edit->render()->crawler()->filter('select[name="update_task_dto[priority]"][disabled]'));
        $edit->submitForm(['update_task_dto' => ['title' => 'Priorytet zmieniony', 'priority' => 'high']], 'save');
        self::assertSame('normal', $this->tasks()->get($task->id)->priority->value, 'Fixed for a user: what was sent is ignored.');
        self::assertSame('Priorytet zmieniony', $this->tasks()->get($task->id)->title);

        $admin = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'id' => $task->id], $this->client)->actingAs(self::admin());
        $admin->submitForm(['update_task_dto' => ['title' => 'Priorytet zmieniony', 'priority' => 'high']], 'save');
        self::assertSame('high', $this->tasks()->get($task->id)->priority->value);
    }

    public function testATaskStartsAsACopyOfAnother(): void
    {
        $task = $this->find('Describe the hand grinder');
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'task', 'from' => $task->id], $this->client)->actingAs(self::admin());
        $crawler = $form->render()->crawler();

        // What "read" says, onto the fields of the add DTO: the title, the priority (an enum, as its value).
        self::assertSame($task->title, $crawler->filter('input[name="create_task_dto[title]"]')->attr('value'));
        self::assertSame($task->priority->value, $crawler->filter('select[name="create_task_dto[priority]"] option[selected]')->attr('value'));
    }

    public function testDetailsOfATask(): void
    {
        $task = $this->find('Describe the hand grinder');
        $this->client->loginUser(self::user());

        $this->client->request('GET', '/tasks/' . $task->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Describe the hand grinder');
        // "details" leaves the id out.
        self::assertSelectorTextNotContains('main', $task->id);

        $this->client->request('GET', '/tasks/no-such-task');
        self::assertResponseStatusCodeSame(404);
    }

    public function testADoneTaskIsNotEditedAnyMore(): void
    {
        $done = array_find($this->tasks()->all(), static fn (Task $task): bool => $task->done);
        $this->client->loginUser(self::user());
        $this->client->request('GET', '/tasks/' . $done->id . '/edit');
        self::assertResponseStatusCodeSame(403);

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());
        $rows = $list->render()->crawler();
        self::assertCount(0, $rows->filter('tr[id="task-' . $done->id . '"] a[href$="/edit"]'), 'DoneTaskGuard takes its edit button away.');
        self::assertCount(3, $rows->filter('tbody tr[id] a[href$="/edit"]'));
    }

    public function testAListenerRefusesDeletingAndTheDetailsPageDeletes(): void
    {
        $open = $this->find('Describe the hand grinder');
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::admin());
        $list->call('delete', ['id' => $open->id]);
        self::assertNotNull($this->find('Describe the hand grinder'), 'UnfinishedTaskDeleting refuses it.');

        $done = array_find($this->tasks()->all(), static fn (Task $task): bool => $task->done);
        $delete = $this->createLiveComponent('Crud:Delete', ['resource' => 'task', 'id' => $done->id], $this->client)->actingAs(self::admin());
        self::assertCount(1, $delete->render()->crawler()->filter('button[data-live-action-param="delete"]'));
        $delete->call('delete');
        self::assertNull(array_find($this->tasks()->all(), static fn (Task $task): bool => $task->id === $done->id));
    }

    public function testOnlyAnAdministratorDeletes(): void
    {
        $done = array_find($this->tasks()->all(), static fn (Task $task): bool => $task->done);

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::user());
        self::assertCount(0, $list->render()->crawler()->filter('[data-live-action-param="delete"]'), 'No delete button.');
        $list->call('delete', ['id' => $done->id]);
        self::assertNotNull(array_find($this->tasks()->all(), static fn (Task $task): bool => $task->id === $done->id), 'Refused also when asked for.');

        $this->client->catchExceptions(false);
        try {
            $this->createLiveComponent('Crud:Delete', ['resource' => 'task', 'id' => $done->id], $this->client)->actingAs(self::user())->call('delete');
            self::fail('The delete button of a page is not for a user either.');
        } catch (AccessDeniedHttpException) {
        }
        $this->client->catchExceptions(true);

        $asAdmin = $this->createLiveComponent('Crud:List', ['resource' => 'task'], $this->client)->actingAs(self::admin());
        self::assertGreaterThan(0, $asAdmin->render()->crawler()->filter('[data-live-action-param="delete"]')->count());
    }

    public function testAListenerRefusesSaving(): void
    {
        $this->client->disableReboot();
        static::getContainer()->get('event_dispatcher')->addListener(RecordSaving::class, static function (RecordSaving $event): void {
            throw new CrudFailure(422, 'No.', 'title', translationKey: 'app.task.unfinished');
        });
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'task'], $this->client)->actingAs(self::user());

        $form->submitForm(['create_task_dto' => ['title' => 'Odmówione', 'priority' => 'high']], 'save');

        // Shown at the field the failure names.
        self::assertStringContainsString('Najpierw zakończ zadanie', $form->render()->crawler()->filter('#create_task_dto_title')->ancestors()->first()->text());
        self::assertNull($this->find('Odmówione'));
    }

    private function tasks(): Tasks
    {
        return static::getContainer()->get(Tasks::class);
    }

    private function find(string $title): ?Task
    {
        return array_find($this->tasks()->all(), static fn (Task $task): bool => $task->title === $title);
    }
}
