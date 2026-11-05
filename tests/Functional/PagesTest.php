<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use PHPUnit\Framework\Attributes\DataProvider;

final class PagesTest extends PanelTestCase
{
    public function testAnonymousIsSentToSignIn(): void
    {
        $this->client->request('GET', '/tasks');

        self::assertResponseRedirects('/login');
    }

    /** @return iterable<array{string, string}> */
    public static function adminPages(): iterable
    {
        yield ['/', 'Pulpit'];
        yield ['/tasks', 'Zadania'];
        yield ['/tasks/new', 'Nowe zadanie'];
        yield ['/admin', 'Pulpit'];
        yield ['/admin/products', 'Produkty'];
        yield ['/admin/products/new', 'Nowy produkt'];
        yield ['/admin/categories', 'Kategorie'];
        yield ['/admin/categories/new', 'Nowa kategoria'];
    }

    #[DataProvider('adminPages')]
    public function testAdminSeesThePage(string $path, string $heading): void
    {
        $this->client->loginUser(self::admin());
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $heading);
    }

    public function testRecordPages(): void
    {
        $this->client->loginUser(self::admin());
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002']);

        $this->client->request('GET', '/admin/products/' . $product->id);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Travel mug');

        $this->client->request('GET', '/admin/products/' . $product->id . '/edit');
        self::assertResponseIsSuccessful();
        self::assertInputValueSame('product[sku]', 'MUG-002');

        $this->client->request('GET', '/admin/products/999999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testAPageAddedToTheRecordsWithAsCrudPage(): void
    {
        $this->client->loginUser(self::admin());
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002']);
        $stats = '/admin/products/' . $product->id . '/stats';

        // Led to from the record.
        $crawler = $this->client->request('GET', '/admin/products/' . $product->id);
        self::assertCount(1, $crawler->filter('a[href="' . $stats . '"]'));

        $crawler = $this->client->request('GET', $stats);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Statystyki');
        self::assertSelectorTextContains('h1', 'Travel mug');
        self::assertSelectorTextContains('body', 'Wartość magazynu');
        // The way back to the record, and the menu of its panel with its resource lit.
        self::assertCount(1, $crawler->filter('a[href="/admin/products/' . $product->id . '"]'));
        self::assertSame(['/admin/products'], $crawler->filter('[data-panel-nav] a[aria-current=page]')->each(static fn ($link): string => (string) $link->attr('href')));

        $this->client->request('GET', '/admin/products/999999/stats');
        self::assertResponseStatusCodeSame(404);
    }

    public function testAPageOfTheApplicationWithMapCrud(): void
    {
        $this->client->loginUser(self::admin());
        $category = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002'])->category;
        $summary = '/admin/categories/' . $category->id . '/summary';

        // Led to by the RouteAction of the category.
        $crawler = $this->client->request('GET', '/admin/categories/' . $category->id);
        self::assertCount(1, $crawler->filter('a[href="' . $summary . '"]'));

        $crawler = $this->client->request('GET', $summary);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Podsumowanie');
        self::assertSelectorTextContains('h1', $category->name);
        // No "_scope" on the route: the panel is that of the resource, with the resource lit in its menu.
        self::assertSame(['/admin/categories'], $crawler->filter('[data-panel-nav] a[aria-current=page]')->each(static fn ($link): string => (string) $link->attr('href')));
        self::assertCount(1, $crawler->filter('a[href="/admin/categories/' . $category->id . '"]'));

        $this->client->request('GET', '/admin/categories/999999/summary');
        self::assertResponseStatusCodeSame(404);
    }

    public function testUserHasNoAdminPanel(): void
    {
        $this->client->loginUser(self::user());

        $this->client->request('GET', '/admin/products');
        self::assertResponseStatusCodeSame(403);

        // Nor the way into it.
        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('a[href="/admin"]'));
    }

    public function testMenuOfAPanel(): void
    {
        $this->client->loginUser(self::admin());
        $crawler = $this->client->request('GET', '/admin');

        $links = $crawler->filter('[data-panel-nav] a')->each(static fn ($link): string => (string) $link->attr('href'));
        self::assertContains('/admin/products', $links);
        self::assertContains('/admin/categories', $links);
        self::assertNotContains('/tasks', $links);
    }

    public function testAPageOfTheWholeResource(): void
    {
        $this->client->loginUser(self::admin());

        // Led to from the list, next to "new" - not from a record.
        $list = $this->client->request('GET', '/admin/categories');
        self::assertCount(1, $list->filter('a[href="/admin/categories/overview"]'));
        self::assertCount(0, $list->filter('[data-live-name-value] a[href$="/overview"]'), 'No action of a record.');

        $page = $this->client->request('GET', '/admin/categories/overview');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Coffee', $page->filter('[data-overview]')->text());

        // Only for those who may see the list.
        $this->client->loginUser(self::user());
        $this->client->catchExceptions(true);
        $this->client->request('GET', '/admin/categories/overview');
        self::assertResponseStatusCodeSame(403);
    }
}
