<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductNote;
use Byfareska\Crud\Attribute\AsCrud;
use Byfareska\Crud\Driver\Doctrine\DoctrineDriver;
use Byfareska\Crud\Driver\Doctrine\DoctrineSource;
use Byfareska\Crud\Event\RecordChanged;
use Byfareska\Crud\Event\RecordCopying;
use Byfareska\Crud\Event\RecordDeleted;
use Byfareska\Crud\Event\RecordSaved;
use Byfareska\Crud\Event\RecordSaving;
use Byfareska\Crud\Resource\CrudFailure;
use Byfareska\Crud\Resource\CrudListQuery;
use Byfareska\Crud\Resource\CrudRecords;
use Byfareska\Crud\Resource\CrudRegistry;
use Byfareska\Crud\UseCase\ListPage;
use DateTimeImmutable;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The "doctrine" driver, through the live components of the pages.
 */
final class DoctrineCrudTest extends PanelTestCase
{
    public function testListSearchesAcrossARelation(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        self::assertCount(12, $list->render()->crawler()->filter('tbody tr[id]'));

        $list->set('filters', ['query' => 'mug']);
        self::assertCount(3, $list->render()->crawler()->filter('tbody tr[id]'));

        // "category.name" is one of the fields searched.
        $list->set('filters', ['query' => 'tea']);
        self::assertCount(3, $list->render()->crawler()->filter('tbody tr[id]'));
    }

    public function testListFiltersAndSorts(): void
    {
        $category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->set('filters', ['category' => (string) $category->id]);
        self::assertCount(3, $list->render()->crawler()->filter('tbody tr[id]'));

        $list->set('filters', ['active' => '0']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Sencha', $rows->text());

        $list->set('filters', []);
        $list->call('sortBy', ['field' => 'price']);
        $first = $list->render()->crawler()->filter('tbody tr[id]')->first()->text();
        self::assertStringContainsString('Paper filters', $first);
    }

    public function testAPercentAndAnUnderscoreTypedAreThemselves(): void
    {
        foreach (['Filtr 100% bawełna' => 'FIL-100P', 'Filtr 1000 sztuk' => 'FIL-1000', 'Filtr a_b' => 'FIL-AB', 'Filtr axb' => 'FIL-AXB'] as $name => $sku) {
            $product = new Product();
            $product->name = $name;
            $product->sku = $sku;
            $this->entities()->persist($product);
        }
        $this->entities()->flush();
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->set('filters', ['query' => '100%']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('100% bawełna', $rows->text());

        $list->set('filters', ['query' => 'a_b']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Filtr a_b', $rows->text());
    }

    public function testARelationFilterIsSearchedAsOneTypes(): void
    {
        $this->client->loginUser(self::admin());
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $url = $list->render()->crawler()
            ->filter('[data-symfony--ux-autocomplete--autocomplete-url-value*="byfareska_crud_relation"]')
            ->attr('data-symfony--ux-autocomplete--autocomplete-url-value');
        self::assertNotNull($url);

        $this->client->request('GET', $url . '&query=cof');
        self::assertResponseIsSuccessful();
        $results = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(['Coffee'], array_column($results['results'], 'text'));
    }

    public function testTheAddressKeepsOnlyTheFiltersInUse(): void
    {
        $category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        // The address of the page, as the browser sends it with each render.
        $this->client->setServerParameter('HTTP_X_LIVE_URL', '/admin/products?ref=menu');

        $list->set('filters', [
            'query' => '',
            'category' => ['comparison' => 'eq', 'value' => (string) $category->id],
            'createdAt' => ['comparison' => 'between', 'value' => null, 'to' => null],
            'priceFrom' => '',
        ]);
        self::assertSame(
            '/admin/products?ref=menu&' . http_build_query(['filters' => ['category' => ['comparison' => 'eq', 'value' => (string) $category->id]]]),
            $list->response()->headers->get('X-Live-Url'),
        );

        $list->set('filters', ['category' => ['comparison' => 'eq', 'value' => '']]);
        self::assertSame('/admin/products?ref=menu', $list->response()->headers->get('X-Live-Url'));
    }

    public function testAFilterOfTheApplicationsOwnAndSortingByARelation(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->set('filters', ['priceFrom' => '50', 'priceTo' => '90']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertSame(4, $rows->count(), $rows->text(''));

        $list->set('filters', ['priceFrom' => '100']);
        self::assertStringContainsString('Hand grinder', $list->render()->crawler()->filter('tbody tr[id]')->text());

        // "category" orders by "category.name".
        $list->set('filters', []);
        $list->call('sortBy', ['field' => 'category']);
        self::assertStringContainsString('Accessories', $list->render()->crawler()->filter('tbody tr[id]')->first()->text());
        $list->call('sortBy', ['field' => 'category']);
        self::assertStringContainsString('Tea', $list->render()->crawler()->filter('tbody tr[id]')->first()->text());
    }

    public function testAnExtensionChangesTheQueryOfTheList(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $queries = self::getContainer()->get('doctrine.debug_data_holder');
        self::assertInstanceOf(DebugDataHolder::class, $queries);
        $this->entities()->clear();
        $queries->reset();

        $list->render();

        // ProductCategoryJoin reads the categories with the products, nobody searching.
        $sql = array_column($queries->getData()['default'] ?? [], 'sql');
        self::assertNotSame([], preg_grep('/ FROM product \w+ LEFT JOIN category /i', $sql), implode("\n", $sql));
    }

    public function testAScopeNarrowsTheListAndEveryRecordFoundByItsId(): void
    {
        $ahead = new Product();
        $ahead->name = 'Christmas mug';
        $ahead->sku = 'MUG-099';
        $ahead->createdAt = new DateTimeImmutable('+1 month');
        $this->entities()->persist($ahead);
        $this->entities()->flush();
        $id = (string) $ahead->id;

        // ProductsAddedSoFar: what is added with a date ahead is not in the panel yet.
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $list->set('filters', ['query' => 'mug']);
        self::assertCount(3, $list->render()->crawler()->filter('tbody tr[id]'));

        // The live component lets the exceptions through; the pages answer them.
        $this->client->catchExceptions(true);
        $this->client->loginUser(self::admin());
        foreach (['', '/edit'] as $page) {
            $this->client->request('GET', '/admin/products/' . $id . $page);
            self::assertResponseStatusCodeSame(404, $page);
        }
        $found = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002']);
        $this->client->request('GET', '/admin/products/' . $found->id);
        self::assertResponseIsSuccessful();

        $list->call('delete', ['id' => $id]);
        $this->entities()->clear();
        self::assertNotNull($this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-099']));
    }

    public function testASortOfTheApplicationsOwn(): void
    {
        $empty = new Category();
        $empty->name = 'Gifts';
        $this->entities()->persist($empty);
        $more = new Product();
        $more->name = 'Teapot';
        $more->sku = 'ACC-004';
        $more->category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Accessories']);
        $this->entities()->persist($more);
        $this->entities()->flush();

        // ProductCount: a subquery, no field of the entity.
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $list->call('sortBy', ['field' => 'productCount']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(5, $rows);
        self::assertStringContainsString('Gifts', $rows->first()->text());
        $list->call('sortBy', ['field' => 'productCount']);
        $first = $list->render()->crawler()->filter('tbody tr[id]')->first()->text();
        self::assertStringContainsString('Accessories', $first);
        self::assertStringContainsString('4', $first);
    }

    public function testAJoinOfACollectionStillPagesByTheEntity(): void
    {
        // "products.name" joins the products: one row of the query per product of a category.
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $list->set('perPage', 5);
        $list->set('filters', ['query' => 'e']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(4, $rows, $rows->text(''));
        foreach (['Accessories', 'Coffee', 'Mugs', 'Tea'] as $index => $name) {
            self::assertStringContainsString($name, $rows->eq($index)->text());
        }

        // Only by the name of a product.
        $list->set('filters', ['query' => 'espresso']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('Coffee', $rows->text());
    }

    public function testTheRowsTheApplicationMarksAndAListWithoutThePreview(): void
    {
        $products = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $dimmed = $products->render()->crawler()->filter('tbody tr[data-inactive]');
        self::assertCount(1, $dimmed);
        self::assertStringContainsString('Sencha', $dimmed->text());
        self::assertStringContainsString('opacity-50', (string) $dimmed->attr('class'));

        $categories = $this->createLiveComponent('Crud:List', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $crawler = $categories->render()->crawler();
        self::assertCount(0, $crawler->filter('[data-live-action-param="preview"]'));
        self::assertMatchesRegularExpression('#/admin/categories/\d+$#', (string) $crawler->filter('tbody tr[id] td a')->first()->attr('href'));
    }

    public function testATemplateOfTheResourcesOwnChangesOneBlock(): void
    {
        $list = $this->createLiveComponent('App:ProductList', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $list->set('filters', ['query' => 'nic-takiego-nie-ma']);
        $crawler = $list->render()->crawler();

        // Its own block...
        self::assertCount(1, $crawler->filter('[data-product-empty]'));
        self::assertStringContainsString('Zmień albo wyczyść filtry.', $crawler->filter('[data-product-empty]')->text());
        // ...and the rest the bundle's.
        self::assertCount(1, $crawler->filter('[data-live-action-param="clearFilters"]'));
        self::assertCount(1, $crawler->filter('select[data-live-set-model-value="perPage"]'));
    }

    public function testARecordIsChangedOnTheServerByARunAction(): void
    {
        $this->client->loginUser(self::admin());
        $this->client->request('GET', '/admin/products');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-live-name-value="App:ProductList"]');

        $products = $this->entities()->getRepository(Product::class);
        $mug = $products->findOneBy(['sku' => 'MUG-001']);
        self::assertTrue($mug->active);
        $heard = [];
        $this->client->disableReboot();
        self::getContainer()->get('event_dispatcher')->addListener(
            RecordChanged::class,
            static function (RecordChanged $event) use (&$heard): void {
                $heard[] = $event->action . ' ' . $event->id;
            },
        );
        $list = $this->createLiveComponent('App:ProductList', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $button = $list->render()->crawler()->filter('tr[id="product-' . $mug->id . '"] [data-live-action-param="run"]');
        self::assertCount(1, $button);
        self::assertSame('toggle-active', $button->attr('data-live-name-param'));

        $list->call('run', ['name' => 'toggle-active', 'id' => (string) $mug->id]);
        $this->entities()->clear();
        self::assertFalse($products->findOneBy(['sku' => 'MUG-001'])->active);
        self::assertSame(['toggle-active ' . $mug->id], $heard);

        // Not an action of the resource: nothing is done.
        $list->call('run', ['name' => 'nothing', 'id' => (string) $mug->id]);
        $this->entities()->clear();
        self::assertFalse($products->findOneBy(['sku' => 'MUG-001'])->active);

        // On the details page, its own component - and the page again, with the record as it is now.
        $this->client->request('GET', '/admin/products/' . $mug->id);
        self::assertSelectorExists('[data-live-name-value="Crud:Run"]');
        $run = $this->createLiveComponent(
            'Crud:Run',
            ['resource' => 'product', 'id' => (string) $mug->id, 'action' => 'toggle-active'],
            $this->client,
        )->actingAs(self::admin());
        self::assertCount(1, $run->render()->crawler()->filter('[data-live-action-param="run"]'));
        $run->call('run');
        $this->entities()->clear();
        self::assertTrue($products->findOneBy(['sku' => 'MUG-001'])->active);
        self::assertSame(['toggle-active ' . $mug->id, 'toggle-active ' . $mug->id], $heard);
    }

    public function testThePageSizeIsChosenOfThoseTheResourceOffers(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $crawler = $list->render()->crawler();
        // byfareska_crud.per_page, shown at first default_per_page (15), not the first of them.
        self::assertSame(['5', '15', '30', '50', '100'], $crawler->filter('select[data-live-set-model-value="perPage"] option')->extract(['value']));
        self::assertSame('15', $crawler->filter('select[data-live-set-model-value="perPage"] option[selected]')->attr('value'));

        $list->set('page', 2);
        $list->set('perPage', 30);
        self::assertSame('30', $list->render()->crawler()->filter('select[data-live-set-model-value="perPage"] option[selected]')->attr('value'));
        self::assertSame(1, $list->component()->page, 'Another size starts from the first page.');

        // A size the resource does not offer is the one it shows at first.
        $list->set('perPage', 7);
        self::assertSame('15', $list->render()->crawler()->filter('select[data-live-set-model-value="perPage"] option[selected]')->attr('value'));
    }

    public function testBulkActionsOfTheBundleAndOfTheApplication(): void
    {
        $products = $this->entities()->getRepository(Product::class);
        $mug = (string) $products->findOneBy(['sku' => 'MUG-001'])->id;
        $tea = (string) $products->findOneBy(['sku' => 'TEA-001'])->id;
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        self::assertCount(12, $list->render()->crawler()->filter('tbody input[data-model="selected[]"]'));

        $list->set('selected', [$mug, $tea]);
        $bar = $list->render()->crawler();
        self::assertSame(['deactivate', 'delete'], $bar->filter('[data-live-action-param="bulk"]')->extract(['data-live-name-param']));
        self::assertStringContainsString('(2)', (string) $bar->filter('[data-live-name-param="delete"]')->attr('data-confirm-message-value'));

        $list->call('bulk', ['name' => 'deactivate']);
        $this->entities()->clear();
        self::assertFalse($products->findOneBy(['sku' => 'MUG-001'])->active);
        self::assertSame([], $list->component()->selected, 'Done: nothing is chosen any more.');

        $list->set('selected', [$mug, $tea]);
        $list->call('bulk', ['name' => 'delete']);
        $this->entities()->clear();
        self::assertNull($products->findOneBy(['sku' => 'MUG-001']));
        self::assertNull($products->findOneBy(['sku' => 'TEA-001']));
    }

    public function testAFilterWithoutATypeKeepsTheOptionsGiven(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        self::assertSame('32', $list->render()->crawler()->filter('[name="filters[sku][value]"]')->attr('maxlength'));
    }

    public function testAFilterOfOneOfSeveralAndOfNothingAtAll(): void
    {
        $categories = $this->entities()->getRepository(Category::class);
        $products = $this->entities()->getRepository(Product::class);
        $coffee = $categories->findOneBy(['name' => 'Coffee']);
        $tea = $categories->findOneBy(['name' => 'Tea']);
        $loose = new Product();
        $loose->name = 'Bez kategorii';
        $loose->sku = 'BEZ-001';
        $loose->createdAt = new DateTimeImmutable('2020-01-01');
        $this->entities()->persist($loose);
        $this->entities()->flush();
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product', 'perPage' => 100], $this->client)->actingAs(self::admin());
        $rows = static fn (): int => $list->render()->crawler()->filter('tbody tr[id]')->count();

        $list->set('filters', ['category' => ['comparison' => 'in', 'values' => [(string) $coffee->id, (string) $tea->id]]]);
        self::assertSame($products->count(['category' => [$coffee, $tea]]), $rows());

        $list->set('filters', ['category' => ['comparison' => 'not_in', 'values' => [(string) $coffee->id]]]);
        self::assertSame($products->count([]) - $products->count(['category' => $coffee]), $rows(), 'Also those without a category.');

        // Nothing to type.
        $list->set('filters', ['category' => ['comparison' => 'empty']]);
        self::assertSame(1, $rows());
        $list->set('filters', ['category' => ['comparison' => 'filled']]);
        self::assertSame($products->count([]) - 1, $rows());
    }

    public function testAListEmbeddedUnderARecordKeepsToItAndToItsOwnAddress(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $products = $this->entities()->getRepository(Product::class);
        $ofCoffee = $products->count(['category' => $coffee]);
        $other = $products->createQueryBuilder('p')->where('p.category != :coffee')->setParameter('coffee', $coffee)
            ->setMaxResults(1)->getQuery()->getSingleResult();

        // The products of one category on its details: two lists of a page keep their state apart.
        $list = $this->createLiveComponent('Crud:List', [
            'resource' => 'product',
            'fixedFilters' => ['category' => ['comparison' => 'eq', 'value' => (string) $coffee->id]],
            'urlPrefix' => 'products',
        ], $this->client)->actingAs(self::admin());
        $crawler = $list->render()->crawler();
        self::assertCount($ofCoffee, $crawler->filter('tbody tr[id]'));
        self::assertCount(0, $crawler->filter('[name="filters[category][value]"]'), 'Not a filter one changes.');
        $mapping = json_decode((string) $crawler->filter('[data-live-name-value="Crud:List"]')->attr('data-live-query-mapping-value'), true);
        self::assertSame('products_filters', $mapping['filters']['name'] ?? null);
        self::assertSame('products_page', $mapping['page']['name'] ?? null);

        // Neither what the browser sends nor "clear filters" takes it away.
        $list->set('filters', ['category' => ['comparison' => 'eq', 'value' => (string) $other->category->id]]);
        $list->call('clearFilters');
        self::assertCount($ofCoffee, $list->render()->crawler()->filter('tbody tr[id]'));

        // A bulk action gets only its records.
        $list->set('selected', [(string) $other->id]);
        $list->call('bulk', ['name' => 'deactivate']);
        $this->entities()->clear();
        self::assertTrue($products->find($other->id)->active, 'Of another category: not changed.');

        $hidden = $this->createLiveComponent('Crud:List', ['resource' => 'product', 'inUrl' => false], $this->client)->actingAs(self::admin());
        self::assertNull($hidden->render()->crawler()->filter('[data-live-name-value="Crud:List"]')->attr('data-live-query-mapping-value'));
    }

    public function testANewRecordStartsAsACopyOfAnother(): void
    {
        $this->client->disableReboot();
        $mug = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-001']);
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $href = $list->render()->crawler()->filter(sprintf('tr[id="product-%d"] [data-action-name="duplicate"], tr[id="product-%1$d"] a[href*="from="]', $mug->id))->attr('href');
        self::assertSame(sprintf('/admin/products/new?from=%d', $mug->id), $href);

        // A listener copies more, or changes the copy.
        static::getContainer()->get('event_dispatcher')->addListener(RecordCopying::class, static function (RecordCopying $event): void {
            $event->data->name .= ' (kopia)';
        });
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product', 'from' => (string) $mug->id], $this->client)->actingAs(self::admin());
        $crawler = $form->render()->crawler();
        self::assertSame($mug->name . ' (kopia)', $crawler->filter('input[name="product[name]"]')->attr('value'));
        self::assertSame((string) $mug->category?->id, $crawler->filter('select[name="product[category]"] option[selected]')->attr('value'));

        $form->submitForm(['product' => ['sku' => 'MUG-COPY']], 'save');
        $copy = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-COPY']);
        self::assertNotNull($copy);
        self::assertNotSame($mug->id, $copy->id);
        self::assertEqualsWithDelta((float) $mug->price, (float) $copy->price, 0.001);
    }

    public function testTheFormThemesOfTheApplicationWinInThePanel(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        // money_widget of form/panel_fields.html.twig, not of the theme's form layout: the currency in front.
        self::assertNotEmpty($form->render()->crawler()->filter('[data-app-money] input[name="product[price]"]')->count());
    }

    public function testTheRecordsOfAListOutsideIt(): void
    {
        // An export, a job of a queue: what the list shows for these filters and this order, read page by page.
        $this->signIn(self::admin());
        $query = static::getContainer()->get(CrudListQuery::class);
        $crud = static::getContainer()->get(CrudRegistry::class)->get('product');
        $filters = ['query' => 'mug'];

        $items = iterator_to_array($query->items($crud, $filters, 'name', perPage: 1), false);
        $names = array_column($items, 'name');
        $sorted = $names;
        sort($sorted);
        self::assertSame($sorted, $names, 'In the order asked for.');
        $page = $query->page($crud, $filters, 'name');
        self::assertSame($page->total, count($items), 'Every match, on every page.');
        self::assertSame(
            array_map('strval', array_column($items, 'id')),
            iterator_to_array($query->ids($crud, $filters, 'name'), false),
            'The ids alone (ListsIds): the same records in the same order.',
        );

        // Only for somebody who may see the list.
        $this->signIn(self::user());
        $this->expectException(AccessDeniedHttpException::class);
        $query->page($crud);
    }

    private function signIn(UserInterface $user): void
    {
        static::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));
    }

    public function testABulkActionGetsOnlyRecordsTheListCouldShow(): void
    {
        $ahead = new Product();
        $ahead->name = 'Christmas mug';
        $ahead->sku = 'MUG-099';
        $ahead->active = true;
        $ahead->createdAt = new DateTimeImmutable('+1 month');
        $this->entities()->persist($ahead);
        $this->entities()->flush();
        $mug = (string) $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-001'])->id;

        // Chosen in the browser, which may name any id: one the scope (ProductsAddedSoFar) keeps out is left out.
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $list->set('selected', [$mug, (string) $ahead->id, '999999']);
        $list->call('bulk', ['name' => 'deactivate']);

        $this->entities()->clear();
        $products = $this->entities()->getRepository(Product::class);
        self::assertFalse($products->findOneBy(['sku' => 'MUG-001'])->active);
        self::assertTrue($products->findOneBy(['sku' => 'MUG-099'])->active, 'Not in the panel: not changed.');
    }

    public function testABulkActionOfEveryRecordTheFiltersMatch(): void
    {
        for ($i = 1; $i <= 20; ++$i) {
            $product = new Product();
            $product->name = sprintf('Partia %02d', $i);
            $product->sku = sprintf('PAR-%03d', $i);
            $product->stock = 5;
            $product->active = true;
            $product->createdAt = new DateTimeImmutable('2020-01-01');
            $this->entities()->persist($product);
        }
        $this->entities()->flush();
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $list->set('filters', ['query' => 'Partia']);

        $list->call('selectPage');
        self::assertCount(15, $list->component()->selected, 'A page of 15.');
        $offer = $list->render()->crawler()->filter('[data-live-action-param="selectAllMatching"]');
        self::assertCount(1, $offer);
        self::assertStringContainsString('20', $offer->text());

        $list->call('selectAllMatching');
        $bar = $list->render()->crawler();
        self::assertTrue($list->component()->allMatching);
        self::assertSame([], $list->component()->selected, 'Not thousands of ids: a flag.');
        self::assertStringContainsString('(20)', (string) $bar->filter('[data-live-name-param="delete"]')->attr('data-confirm-message-value'));

        $list->call('bulk', ['name' => 'deactivate']);
        $this->entities()->clear();
        $products = $this->entities()->getRepository(Product::class);
        self::assertSame(0, $products->count(['active' => true, 'sku' => array_map(static fn (int $i): string => sprintf('PAR-%03d', $i), range(1, 20))]), 'Every match, on both pages.');
        self::assertTrue($products->findOneBy(['sku' => 'MUG-001'])->active, 'Nothing the filters do not match.');
        self::assertFalse($list->component()->allMatching, 'Done: nothing is chosen any more.');

        // Other filters forget it.
        $list->call('selectAllMatching');
        $list->set('filters', ['query' => 'Mug']);
        $list->render();
        self::assertFalse($list->component()->allMatching);
    }

    public function testTheIdsOfEveryMatchAreThoseOfTheListInItsOrder(): void
    {
        $registry = static::getContainer()->get(CrudRegistry::class);
        $driver = static::getContainer()->get(DoctrineDriver::class);
        $crud = $registry->get('category');
        // Through a collection (products.name) and by a HIDDEN expression (the number of products).
        $form = static fn (): FormInterface => $driver->listForm($crud)->submit([
            'query' => 'a',
            'sort' => ['-productCount'],
            'page' => '1',
            'perPage' => '1000',
            'fields' => ['id'],
        ]);

        $listed = array_map(static fn (array $item): string => (string) $item['id'], $driver->list($crud, $form())->items);
        self::assertNotSame([], $listed);
        // Given as read, row by row.
        self::assertSame($listed, iterator_to_array($driver->ids($crud, $form()) ?? [], false));
    }

    public function testAListTooLargeToCountIsCountedSoFar(): void
    {
        $driver = static::getContainer()->get(DoctrineDriver::class);
        $crud = new AsCrud(
            name: 'counted_product',
            path: '/counted-products',
            icon: 'box',
            source: new DoctrineSource(Product::class, sort: ['name'], countLimit: 5),
            columns: ['name'],
        );
        $form = $driver->listForm($crud)->submit(['page' => '1', 'perPage' => '3', 'sort' => ['name'], 'fields' => ['id', 'name']]);

        $page = $driver->list($crud, $form);

        self::assertInstanceOf(ListPage::class, $page);
        self::assertSame(5, $page->total);
        self::assertTrue($page->more, 'There are 12: more than it counted.');
        self::assertCount(3, $page->items);
        self::assertSame(2, $page->pageCount);

        $all = new AsCrud(name: 'all_products', path: '/all-products', icon: 'box', source: new DoctrineSource(Product::class, countLimit: 100), columns: ['name']);
        $page = $driver->list($all, $driver->listForm($all)->submit(['page' => '1', 'perPage' => '3', 'fields' => ['id', 'name']]));
        self::assertInstanceOf(ListPage::class, $page);
        self::assertSame(12, $page->total);
        self::assertFalse($page->more);
    }

    public function testASearchOfTheResourcesOwn(): void
    {
        $set = new Product();
        $set->name = 'Mug set with MUG-001';
        $set->sku = 'SET-001';
        $set->createdAt = new DateTimeImmutable('2020-01-01');
        $this->entities()->persist($set);
        $this->entities()->flush();
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        // A whole SKU: that product, not the set whose name mentions it (SkuSearch).
        $list->set('filters', ['query' => 'mug-001']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount(1, $rows);
        self::assertStringContainsString('MUG-001', $rows->text());
        self::assertStringNotContainsString('SET-001', $rows->text());

        // Anything else word by word, as the bundle does.
        $list->set('filters', ['query' => 'mug set']);
        self::assertStringContainsString('SET-001', $list->render()->crawler()->filter('tbody tr[id]')->text());
    }

    public function testAPageHasAsManyAsTheLargestSizeOffered(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        for ($i = 1; $i <= 130; ++$i) {
            $product = new Product();
            $product->name = sprintf('Coffee %03d', $i);
            $product->sku = sprintf('TST-%03d', $i);
            $product->category = $coffee;
            $product->createdAt = new DateTimeImmutable('2020-01-01');
            $this->entities()->persist($product);
        }
        $this->entities()->flush();
        $total = $this->entities()->getRepository(Product::class)->count(['category' => $coffee]);
        self::assertGreaterThan(100, $total);

        $this->client->setServerParameter('HTTP_X_LIVE_URL', sprintf('/admin/categories/%d/products', $coffee->id));
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'category_product'], $this->client)->actingAs(self::admin());
        $list->set('perPage', 150);

        self::assertCount($total, $list->render()->crawler()->filter('tbody tr[id]'), 'Not cut at 100.');
    }

    public function testTheHeadingChoosesTheWholePage(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->call('selectPage');
        self::assertCount(12, $list->component()->selected);
        self::assertCount(1, $list->render()->crawler()->filter('thead input[type="checkbox"][checked]'));

        $list->call('selectPage');
        self::assertSame([], $list->component()->selected);
    }

    public function testCreateAndValidate(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $errors = self::submitInvalid($form, ['product' => ['name' => '', 'sku' => 'zła wartość', 'price' => '10', 'stock' => '1']]);
        self::assertStringContainsString('product.name', $errors);
        self::assertStringContainsString('SKU może mieć tylko wielkie litery', $errors);

        $form->submitForm(['product' => ['name' => 'Mug', 'sku' => 'MUG-009', 'price' => '10,50', 'stock' => '1']], 'save');
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-009']);
        self::assertNotNull($product);
        self::assertSame('10.50', $product->price);
    }

    public function testTheAddressGivesANewRecordOnlyTheFieldsOfItsForm(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product', 'with' => [
            'name' => 'Mug from the address',
            'category' => (string) $coffee->id,
            // No field of the form: not to be set from an address (and a text no date besides).
            'createdAt' => '2000-01-01',
            'id' => '999',
            // A value the field cannot take: left out, not an error.
            'stock' => 'dużo',
        ]], $this->client)->actingAs(self::admin());

        $form->submitForm(['product' => ['sku' => 'MUG-ADR', 'price' => '5', 'stock' => '1']], 'save');

        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-ADR']);
        self::assertNotNull($product);
        self::assertSame('Mug from the address', $product->name);
        self::assertSame($coffee->id, $product->category?->id);
        self::assertNotSame('2000-01-01', $product->createdAt->format('Y-m-d'));
        self::assertNotSame(999, $product->id);
    }

    public function testTheEventsOfASaveKnowWhatTheRecordWasAndWhatIsSaved(): void
    {
        $this->client->disableReboot();
        $events = [];
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->addListener(RecordSaving::class, static function (RecordSaving $event) use (&$events): void {
            $events[] = $event;
        });
        $dispatcher->addListener(RecordSaved::class, static function (RecordSaved $event) use (&$events): void {
            $events[] = $event;
        });
        $category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $category->id], $this->client)->actingAs(self::admin());

        $form->submitForm(['category' => ['name' => 'Coffee beans', 'description' => '']], 'save');

        [$saving, $saved] = $events;
        self::assertInstanceOf(RecordSaving::class, $saving);
        self::assertSame('Coffee', $saving->before?->item['name'], 'Before: as it was, though the form changed the entity.');
        self::assertInstanceOf(RecordSaved::class, $saved);
        self::assertFalse($saved->created);
        self::assertSame('Coffee', $saved->before?->item['name']);
        self::assertInstanceOf(Category::class, $saved->data);
        self::assertSame('Coffee beans', $saved->data->name);
    }

    public function testWhatFailsAfterTheSaveUndoesIt(): void
    {
        $this->client->disableReboot();
        static::getContainer()->get('event_dispatcher')->addListener(RecordSaved::class, static function (): void {
            // E.g. an audit log that could not be written.
            throw new CrudFailure(409, 'No log.', translationKey: 'app.task.unfinished');
        });
        $category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $category->id], $this->client)->actingAs(self::admin());

        $form->submitForm(['category' => ['name' => 'Coffee beans', 'description' => '']], 'save');

        self::assertStringContainsString('Najpierw zakończ zadanie', $form->render()->toString(), 'The refusal is shown.');
        $this->entities()->clear();
        self::assertSame(1, $this->entities()->getRepository(Category::class)->count(['name' => 'Coffee']), 'Nothing is kept.');
        self::assertSame(0, $this->entities()->getRepository(Category::class)->count(['name' => 'Coffee beans']));
    }

    public function testADeletedRecordIsToldWhatItWas(): void
    {
        $this->client->disableReboot();
        $deleted = null;
        static::getContainer()->get('event_dispatcher')->addListener(RecordDeleted::class, static function (RecordDeleted $event) use (&$deleted): void {
            $deleted = $event;
        });
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-001']);
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->call('delete', ['id' => (string) $product->id]);

        self::assertInstanceOf(RecordDeleted::class, $deleted);
        self::assertSame((string) $product->id, $deleted->id);
        self::assertSame('MUG-001', $deleted->record?->item['sku']);
        self::assertNull($deleted->input);

        // Asked why (ConfirmInput of the bulk delete): the reason comes with the action, to the events.
        $tea = (string) $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'TEA-001'])->id;
        $list->set('selected', [$tea]);
        $button = $list->render()->crawler()->filter('[data-live-name-param="delete"]');
        self::assertStringContainsString('Powód', (string) $button->attr('data-confirm-input-value'));
        $list->call('bulk', ['name' => 'delete', 'input' => ' Duplikat ']);
        self::assertSame($tea, $deleted->id);
        self::assertSame('Duplikat', $deleted->input);
    }

    public function testTheEditFormIsAnotherThanTheAddForm(): void
    {
        $add = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        self::assertCount(0, $add->render()->crawler()->filter('input[name="product[sku]"][disabled]'));

        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-001']);
        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'product', 'id' => (string) $product->id], $this->client)->actingAs(self::admin());
        self::assertCount(1, $edit->render()->crawler()->filter('input[name="product[sku]"][disabled]'));

        $edit->submitForm(['product' => ['name' => 'Changed mug', 'sku' => 'MUG-999', 'price' => '10', 'stock' => '3']], 'save');
        $this->entities()->clear();
        $changed = $this->entities()->getRepository(Product::class)->find($product->id);
        self::assertSame('Changed mug', $changed->name);
        self::assertSame('MUG-001', $changed->sku, 'What is sent for a fixed SKU is ignored.');
    }

    public function testASectionOfAnotherPartIsInTheAddFormAndSavedForTheNewRecord(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        self::assertCount(1, $form->render()->crawler()->filter('textarea[name="product[note][note]"]'));

        $form->submitForm(['product' => ['name' => 'Mug', 'sku' => 'MUG-010', 'price' => '10', 'stock' => '1', 'note' => ['note' => 'The handle comes off.']]], 'save');

        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-010']);
        self::assertNotNull($product);
        self::assertSame('The handle comes off.', $this->entities()->find(ProductNote::class, $product->id)?->note);

        // The edit form starts with it.
        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'product', 'id' => (string) $product->id], $this->client)->actingAs(self::admin());
        self::assertSame('The handle comes off.', $edit->render()->crawler()->filter('textarea[name="product[note][note]"]')->text());
    }

    public function testASectionThatRefusesLeavesTheNewRecordUnsaved(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $form->submitForm(['product' => ['name' => 'Mug', 'sku' => 'MUG-011', 'price' => '10', 'stock' => '1', 'note' => ['note' => 'See https://example.com']]], 'save');

        self::assertStringContainsString('Notatka nie może zawierać linków.', $form->render()->crawler()->filter('textarea[name="product[note][note]"]')->ancestors()->first()->text());
        $this->entities()->clear();
        self::assertNull($this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-011']), 'The product is not kept without its section.');
        self::assertSame(0, $this->entities()->getRepository(ProductNote::class)->count([]));
    }

    public function testAResourceUnderARecordWithAParameterOfItsPath(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $ofCoffee = $this->entities()->getRepository(Product::class)->count(['category' => $coffee]);
        self::assertGreaterThan(0, $ofCoffee);
        $this->client->loginUser(self::admin());

        // The way to it from the category.
        $categories = $this->createLiveComponent('Crud:List', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        self::assertCount(1, $categories->render()->crawler()->filter(sprintf('a[href="/admin/categories/%d/products"]', $coffee->id)));

        $crawler = $this->client->request('GET', sprintf('/admin/categories/%d/products', $coffee->id));
        self::assertResponseIsSuccessful();
        self::assertCount($ofCoffee, $crawler->filter('tbody tr[id]'));
        // Every address of the page has the category: those of the rows too.
        $link = (string) $crawler->filter('tbody tr[id] td a')->first()->attr('href');
        self::assertMatchesRegularExpression(sprintf('#^/admin/categories/%d/products/\d+$#', $coffee->id), $link);
        $this->client->request('GET', $link);
        self::assertResponseIsSuccessful();

        // A live request knows it by the address of the page it comes from.
        $this->client->setServerParameter('HTTP_X_LIVE_URL', sprintf('/admin/categories/%d/products', $coffee->id));
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'category_product'], $this->client)->actingAs(self::admin());
        $list->set('filters', ['query' => '']);
        $rows = $list->render()->crawler()->filter('tbody tr[id]');
        self::assertCount($ofCoffee, $rows);
        self::assertStringContainsString(sprintf('/admin/categories/%d/products/', $coffee->id), (string) $rows->filter('td a')->first()->attr('href'));

        // Another category's product is not one of these.
        $other = $this->entities()->createQueryBuilder()->select('p')->from(Product::class, 'p')
            ->where('p.category IS NULL OR p.category != :coffee')->setParameter('coffee', $coffee)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();
        self::assertInstanceOf(Product::class, $other);
        // The live component helper turned the catching of exceptions off; a page answers with its status.
        $this->client->catchExceptions(true);
        $this->client->request('GET', sprintf('/admin/categories/%d/products/%d', $coffee->id, $other->id));
        self::assertResponseStatusCodeSame(404);
    }

    public function testAFormDoesNotSaveOverAChangeMadeSinceItWasOpened(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $coffee->id], $this->client)->actingAs(self::admin());
        self::assertStringContainsString('name="category[_version]"', $form->render()->toString());

        // Somebody else saves it in the meantime.
        $this->entities()->createQuery('UPDATE App\Entity\Category c SET c.description = :d, c.version = c.version + 1 WHERE c.id = :id')
            ->execute(['d' => 'Zmieniona przez kogoś innego', 'id' => $coffee->id]);

        $form->submitForm(['category' => ['name' => 'My coffee', 'description' => 'Mine']], 'save');

        self::assertStringContainsString('zmienił ten rekord', $form->render()->toString());
        $this->entities()->clear();
        $kept = $this->entities()->find(Category::class, $coffee->id);
        self::assertSame('Coffee', $kept?->name);
        self::assertSame('Zmieniona przez kogoś innego', $kept->description);

        // Opened again, it saves.
        $again = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $coffee->id], $this->client)->actingAs(self::admin());
        $again->submitForm(['category' => ['name' => 'My coffee', 'description' => 'Mine']], 'save');
        $this->entities()->clear();
        self::assertSame('My coffee', $this->entities()->find(Category::class, $coffee->id)?->name);
    }

    public function testARecordIsReadWithoutTheCollectionsOfOthers(): void
    {
        $coffee = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $this->entities()->clear();
        $records = static::getContainer()->get(CrudRecords::class);
        $crud = static::getContainer()->get(CrudRegistry::class)->get('category');

        $record = $records->read($crud, (string) $coffee->id);

        self::assertSame('Coffee', $record['name']);
        self::assertArrayNotHasKey('products', $record, 'The inverse side: the products of the category are theirs.');
        $read = $this->entities()->find(Category::class, $coffee->id);
        self::assertFalse($read?->products->isInitialized());
    }

    public function testSaveStaysOnTheRecord(): void
    {
        $this->client->disableReboot();
        $then = [];
        static::getContainer()->get('event_dispatcher')->addListener(RecordSaved::class, static function (RecordSaved $event) use (&$then): void {
            $then[] = $event->then;
        });

        // A new one: its edit form, once it is added.
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $form->submitForm(['category' => ['name' => 'Kategoria zostaje', 'description' => '']], 'save');
        $added = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Kategoria zostaje']);
        self::assertNotNull($added);
        self::assertResponseRedirects(sprintf('/admin/categories/%d/edit', $added->id));

        // Edited: the same form again, on the tab that was in view.
        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $added->id, 'tab' => 'more'], $this->client)->actingAs(self::admin());
        $edit->submitForm(['category' => ['name' => 'Kategoria zostaje dalej', 'description' => '']], 'save');
        self::assertResponseRedirects(sprintf('/admin/categories/%d/edit?tab=more', $added->id));
        self::assertSame(['edit', 'edit'], $then, '"Save" is told by where it leads.');
    }

    public function testTheOtherButtonsOfAFormLeadElsewhere(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $buttons = $form->render()->crawler()->filter('button[data-live-then-param]');
        self::assertSame(['new', 'return'], $buttons->each(static fn ($button): string => (string) $button->attr('data-live-then-param')));

        // The values first, then the button that saves with its own argument.
        $form->submitForm(['category' => ['name' => 'Kategoria testowa', 'description' => '']])->call('save', ['then' => 'new']);
        self::assertResponseRedirects('/admin/categories/new');
        // The toast of the resource's own, where the application has one.
        self::assertSame(['Kategoria dodana'], $this->client->getRequest()->getSession()->getFlashBag()->peek('toast'));

        $added = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Kategoria testowa']);
        self::assertNotNull($added);
        $edit = $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $added->id], $this->client)->actingAs(self::admin());
        // No "add another" when editing; the application's own button (SaveAndSeeProducts) is there.
        self::assertSame(['return', 'products'], $edit->render()->crawler()->filter('button[data-live-then-param]')->each(static fn ($button): string => (string) $button->attr('data-live-then-param')));
        $edit->submitForm(['category' => ['name' => 'Kategoria testowa zmieniona', 'description' => '']])->call('save', ['then' => 'return']);
        self::assertResponseRedirects('/admin/categories');

        $this->client->disableReboot();
        $then = null;
        static::getContainer()->get('event_dispatcher')->addListener(RecordSaved::class, static function (RecordSaved $event) use (&$then): void {
            $then = $event->then;
        });
        $this->createLiveComponent('Crud:Form', ['resource' => 'category', 'id' => (string) $added->id], $this->client)
            ->actingAs(self::admin())->call('save', ['then' => 'products']);
        self::assertResponseRedirects(sprintf('/admin/categories/%d/products', $added->id));
        self::assertSame('products', $then, 'The listeners know which button saved.');
    }

    public function testAPageOfTheResourcesOwnTemplate(): void
    {
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/admin/categories');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-category-note]'));
        self::assertCount(1, $crawler->filter('[data-live-name-value="Crud:List"]'), 'The page of the theme around it.');
        // Blocks of the layout of the panel, filled by the page.
        self::assertCount(1, $crawler->filter('header [data-category-topbar]'));
        self::assertCount(1, $crawler->filter('header [data-category-menu]'), 'In the user menu of the top bar.');
    }

    public function testATakenValueIsAnErrorOfTheForm(): void
    {
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'category'], $this->client)->actingAs(self::admin());

        $errors = self::submitInvalid($form, ['category' => ['name' => 'Coffee']]);

        self::assertStringContainsString('category.name', $errors);
        self::assertSame(1, $this->entities()->getRepository(Category::class)->count(['name' => 'Coffee']));
    }

    public function testDeleteAndRefuseDeletingWhatIsInUse(): void
    {
        $products = $this->entities()->getRepository(Product::class);
        $product = $products->findOneBy(['sku' => 'MUG-001']);
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());

        $list->call('delete', ['id' => (string) $product->id]);
        $this->entities()->clear();
        self::assertNull($products->findOneBy(['sku' => 'MUG-001']));

        // A category with products cannot go.
        $category = $this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']);
        $categories = $this->createLiveComponent('Crud:List', ['resource' => 'category'], $this->client)->actingAs(self::admin());
        $categories->call('delete', ['id' => (string) $category->id]);
        $this->entities()->clear();
        self::assertNotNull($this->entities()->getRepository(Category::class)->findOneBy(['name' => 'Coffee']));
    }

    public function testAnActionAndAFormatOfTheApplicationsOwn(): void
    {
        $list = $this->createLiveComponent('Crud:List', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $mug = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002']);
        $row = $list->render()->crawler()->filter('tr#product-' . $mug->id);

        // CopyAction: a button of the row (inline), made by CopyActionHandler.
        $copy = $row->filter('[data-controller="clipboard"]');
        self::assertSame('MUG-002', $copy->attr('data-clipboard-text-value'));
        self::assertSame('Kopiuj SKU', $copy->attr('title'));

        // ComponentFormat: the stock (3) is printed by App:StockLevel, in red below 5.
        self::assertCount(1, $row->filter('span[style*="#dc2626"]'));

        // The same action among the buttons of the details page.
        $this->client->loginUser(self::admin());
        $crawler = $this->client->request('GET', '/admin/products/' . $mug->id);
        self::assertSame('MUG-002', $crawler->filter('[data-controller="clipboard"]')->attr('data-clipboard-text-value'));
    }
}
