<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Supplier;
use Byfareska\Crud\Event\ListStateChanged;

/**
 * The minimal declarations (App\Crud\Doctrine\Minimal, App\Crud\Dto\Minimal): what the defaults give.
 */
final class MinimalCrudTest extends PanelTestCase
{
    public function testDoctrineGivesEveryPageWithAGuessedForm(): void
    {
        $this->client->loginUser(self::user());
        $crawler = $this->client->request('GET', '/suppliers');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Dostawcy');
        self::assertContains('/suppliers', $crawler->filter('[data-panel-nav] a')->each(static fn ($link): string => (string) $link->attr('href')));

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'supplier'], $this->client)->actingAs(self::user());
        self::assertCount(3, $list->render()->crawler()->filter('tbody tr[id]'));

        // The fields guessed from the mapping, checked by the constraints of the entity.
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'supplier'], $this->client)->actingAs(self::user());
        self::assertStringContainsString('supplier.email', self::submitInvalid($form, ['supplier' => ['name' => 'Młyny', 'email' => 'nie-e-mail']]));
        $form->submitForm(['supplier' => ['name' => 'Młyny Polskie', 'email' => 'kontakt@mlyny.example']], 'save');
        $supplier = $this->entities()->getRepository(Supplier::class)->findOneBy(['name' => 'Młyny Polskie']);
        self::assertNotNull($supplier);

        foreach (['', '/edit'] as $page) {
            $this->client->request('GET', '/suppliers/' . $supplier->id . $page);
            self::assertResponseIsSuccessful();
        }

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'supplier'], $this->client)->actingAs(self::user());
        $list->call('delete', ['id' => (string) $supplier->id]);
        $this->entities()->clear();
        self::assertNull($this->entities()->getRepository(Supplier::class)->findOneBy(['name' => 'Młyny Polskie']));
    }

    public function testAListStartsWithItsDefaultsAndTellsWhatIsChosen(): void
    {
        $names = $this->entities()->getRepository(Supplier::class)->createQueryBuilder('s')->select('s.name')
            ->orderBy('s.name', 'DESC')->getQuery()->getSingleColumnResult();

        // SuppliersByNameDescending (tests/Fixtures): the address says nothing, the list starts from Z.
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'supplier'], $this->client)->actingAs(self::user());
        self::assertStringContainsString($names[0], $list->render()->crawler()->filter('tbody tr[id]')->first()->text());
        self::assertSame('-name', $list->component()->sort);

        // What the address says comes first.
        $asked = $this->createLiveComponent('Crud:List', ['resource' => 'supplier', 'sort' => 'name'], $this->client)->actingAs(self::user());
        self::assertSame('name', $asked->component()->sort);

        $this->client->disableReboot();
        $changed = [];
        static::getContainer()->get('event_dispatcher')->addListener(ListStateChanged::class, static function (ListStateChanged $event) use (&$changed): void {
            $changed[] = $event;
        });
        $list->call('sortBy', ['field' => 'email']);
        self::assertCount(1, $changed);
        self::assertSame('supplier', $changed[0]->crud->name);
        self::assertStringStartsWith('email', (string) $changed[0]->sort);
    }

    public function testDtoWithOnlyAListHasNoOtherPage(): void
    {
        $this->client->loginUser(self::user());
        $this->client->request('GET', '/bundles');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Pakiety');
        $this->client->request('GET', '/bundles/new');
        self::assertResponseStatusCodeSame(404);

        $list = $this->createLiveComponent('Crud:List', ['resource' => 'bundle'], $this->client)->actingAs(self::user());
        $bundles = count(static::getContainer()->getParameter('kernel.bundles'));
        // The use case gives every bundle (the list DTO has no "page"): the list pages them itself.
        $crawler = $list->render()->crawler();
        self::assertCount(min(15, $bundles), $crawler->filter('tbody tr[id]'));
        self::assertCount(0, $crawler->filter('a[href^="/bundles/"]'));

        $list->set('perPage', 100);
        self::assertCount($bundles, $list->render()->crawler()->filter('tbody tr[id]'));
        self::assertStringContainsString('Byfareska\Crud', $list->render()->crawler()->filter('tbody')->text());
    }
}
