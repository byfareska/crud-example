<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * The dashboard (its JSON endpoints) and the command palette.
 */
final class PanelFeaturesTest extends PanelTestCase
{
    public function testDefaultDashboardHasTheWidgetsOfThePanel(): void
    {
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Katalog', $crawler->filter('.grid-stack')->text());

        $crawler = $this->client->request('GET', '/');
        self::assertStringContainsString('Zadania', $crawler->filter('.grid-stack')->text());
        self::assertStringNotContainsString('Katalog', $crawler->filter('.grid-stack')->text());
    }

    public function testTheUserMenuSignsOutAndLeadsBackFromActingAsSomebodyElse(): void
    {
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/?_switch_user=user');
        self::assertResponseRedirects();
        $crawler = $this->client->followRedirect();
        $exit = $crawler->filter('a[href*="_switch_user=_exit"]');
        self::assertCount(1, $exit, 'The way back to the own account.');

        $logout = $crawler->filter('form[action*="logout"]');
        self::assertCount(1, $logout);
        // The CSRF token of the firewall's "logout" comes with its address.
        self::assertStringContainsString('_csrf_token=', (string) $logout->attr('action'));
        $this->client->submit($logout->form());
        self::assertResponseRedirects();
        $this->client->request('GET', '/');
        self::assertResponseRedirects();
    }

    public function testAPanelGoesByANameOfItsOwn(): void
    {
        $this->client->loginUser(self::admin());

        $admin = $this->client->request('GET', '/admin');
        self::assertStringEndsWith('· Acme Admin', $admin->filter('title')->text());
        self::assertSame('ltr', $admin->filter('html')->attr('dir'));
        self::assertSame('pl', $admin->filter('html')->attr('lang'));
        // The colour scheme set before the first paint, by the key of the theme controller (of the daisyui theme).
        self::assertStringContainsString('localStorage.getItem("panel-theme-daisyui")', $admin->filter('head script')->first()->text());

        $panel = $this->client->request('GET', '/');
        self::assertStringNotContainsString('Acme Admin', $panel->filter('title')->text());
    }

    public function testTheLiveRequestsOfAPanelAreUnderItsPrefix(): void
    {
        $this->client->loginUser(self::admin());

        // In the firewall of the panel, however it is set up: /admin/..., not LiveComponent's /_components/...
        $crawler = $this->client->request('GET', '/admin/products');
        $url = (string) $crawler->filter('[data-live-name-value="App:ProductList"]')->attr('data-live-url-value');
        self::assertStringStartsWith('/admin/_crud/App:ProductList', $url);

        // A re-render through it, as the browser does it.
        $props = (string) $crawler->filter('[data-live-name-value="App:ProductList"]')->attr('data-live-props-value');
        $this->client->request('POST', $url, ['data' => json_encode(['props' => json_decode($props, true)], JSON_THROW_ON_ERROR)], server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
            'HTTP_X_LIVE_URL' => '/admin/products',
        ]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('/admin/_crud/', (string) $this->client->getResponse()->getContent());

        // The panel without a prefix: its own endpoint too.
        $crawler = $this->client->request('GET', '/tasks');
        self::assertStringStartsWith('/_crud/', (string) $crawler->filter('[data-live-name-value="Crud:List"]')->attr('data-live-url-value'));
    }

    public function testTheMenuAsAnExtensionChangesIt(): void
    {
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/admin');

        $products = $crawler->filter('[data-panel-nav] a[href="/admin/products"]');
        self::assertCount(1, $products);
        self::assertStringContainsString('1', $products->filter('.badge, .rounded-full')->text(), 'Off sale: Sencha.');
        $docs = $crawler->filter('[data-panel-nav] a[href^="https://symfony.com/doc"]');
        self::assertCount(1, $docs);
        self::assertSame('_blank', $docs->attr('target'));
        self::assertSame('noopener', $docs->attr('rel'));
        // Its icon, drawn by the theme's crud/icon.html.twig.
        self::assertCount(1, $docs->filter('i.bi.bi-book[aria-hidden="true"]'));
    }

    public function testTheQuickActionsAreThoseOfTheDashboardsPanel(): void
    {
        $this->client->loginUser(self::admin());
        $tiles = static fn ($crawler): array => $crawler->filter('.grid-stack a[href$="/new"]')->each(
            static fn ($link): string => (string) $link->attr('href'),
        );

        $admin = $tiles($this->client->request('GET', '/admin'));
        self::assertContains('/admin/products/new', $admin);

        $panel = $tiles($this->client->request('GET', '/'));
        self::assertNotSame([], $panel);
        self::assertSame([], array_filter($panel, static fn (string $href): bool => str_starts_with($href, '/admin/')));
    }

    public function testAddMoveAndRemoveAWidget(): void
    {
        $this->client->loginUser(self::user());

        $this->json('POST', '/_dashboard/widget', ['widget' => 'panel.note']);
        self::assertResponseStatusCodeSame(204);
        $crawler = $this->client->request('GET', '/');
        $note = $crawler->filter('.grid-stack-item[gs-id]')->reduce(static fn ($item): bool => str_contains($item->html(), 'Notatka'));
        self::assertCount(1, $note);
        $id = $note->attr('gs-id');

        $placements = $crawler->filter('.grid-stack-item[gs-id]')->each(static fn ($item): array => [
            'id' => $item->attr('gs-id'), 'x' => 0, 'y' => 0, 'w' => 3, 'h' => 2,
        ]);
        $this->json('PUT', '/_dashboard/layout', ['placements' => $placements]);
        self::assertResponseStatusCodeSame(204);

        $this->json('DELETE', '/_dashboard/widget/' . $id);
        self::assertResponseStatusCodeSame(204);
        $crawler = $this->client->request('GET', '/');
        self::assertStringNotContainsString('Notatka', $crawler->filter('.grid-stack')->text());

        $this->json('POST', '/_dashboard/reset');
        self::assertResponseStatusCodeSame(204);
    }

    public function testAPanelHasMoreDashboardsOfItsOwn(): void
    {
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/admin/warehouse');
        self::assertResponseIsSuccessful();
        self::assertSame('/admin/warehouse/_dashboard/widget', $crawler->filter('[data-dashboard-grid-add-url-value]')->attr('data-dashboard-grid-add-url-value'));
        self::assertGreaterThan(0, $crawler->filter('a[href="/admin/warehouse"]')->count(), 'In the menu of the panel.');
        self::assertStringContainsString('Magazyn', $crawler->filter('title')->text(), 'Named as in the menu.');
        self::assertStringContainsString('Magazyn', $crawler->filter('h1')->text());

        // Its layout is its own: a note added there is not on the dashboard of the panel.
        $this->json('POST', '/admin/warehouse/_dashboard/widget', ['widget' => 'panel.note']);
        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Notatka', $this->client->request('GET', '/admin/warehouse')->filter('.grid-stack')->text());
        self::assertStringNotContainsString('Notatka', $this->client->request('GET', '/admin')->filter('.grid-stack')->text());

        // Only for those the panel is for.
        $this->client->loginUser(self::user());
        $this->client->request('GET', '/admin/warehouse');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDashboardRefusesARequestWithoutItsHeader(): void
    {
        $this->client->loginUser(self::user());
        $this->client->request('POST', '/_dashboard/widget', content: '{"widget":"panel.note"}');

        self::assertResponseStatusCodeSame(400);
    }

    public function testOnlyAnAdministratorSharesALayout(): void
    {
        $this->client->loginUser(self::user());
        $this->json('PUT', '/_dashboard/default');
        self::assertResponseStatusCodeSame(403);
    }

    public function testASharedLayoutKeepsTheNoteNotItsText(): void
    {
        $this->client->loginUser(self::admin());
        $this->json('POST', '/_dashboard/widget', ['widget' => 'panel.note']);
        $crawler = $this->client->request('GET', '/');
        $id = $crawler->filter('.grid-stack-item[gs-id]')->reduce(static fn ($item): bool => str_contains($item->html(), 'Notatka'))->attr('gs-id');

        $note = $this->createLiveComponent('Panel:NoteWidget', ['placement' => $id, 'board' => 'panel'], $this->client)->actingAs(self::admin());
        $note->set('text', 'PIN do sejfu: 1234');
        self::assertStringContainsString('PIN do sejfu', $this->client->request('GET', '/')->filter('.grid-stack')->html());

        $this->json('PUT', '/_dashboard/default');
        self::assertResponseStatusCodeSame(204);

        // Somebody without a layout of their own gets the shared one: the note, empty.
        $this->client->loginUser(self::user());
        $grid = $this->client->request('GET', '/')->filter('.grid-stack');
        self::assertStringContainsString('Notatka', $grid->text());
        self::assertStringNotContainsString('PIN do sejfu', $grid->html());

        // The one who shared it still has it.
        $this->client->loginUser(self::admin());
        self::assertStringContainsString('PIN do sejfu', $this->client->request('GET', '/')->filter('.grid-stack')->html());
    }

    public function testPaletteFindsRecordsAndCommands(): void
    {
        $palette = $this->createLiveComponent('Panel:CommandPalette', ['scope' => 'admin'], $this->client)->actingAs(self::admin());

        // A record, found through the list of its resource.
        $palette->set('query', 'travel');
        self::assertStringContainsString('MUG-002', $palette->render()->toString());

        $palette->set('query', 'produkty');
        self::assertStringContainsString('Produkty', $palette->render()->toString());

        $palette->set('query', '>motyw');
        self::assertStringContainsString('data-key="command.theme.toggle"', $palette->render()->toString());
    }

    public function testPaletteOfAUserHasNoAdminPages(): void
    {
        $palette = $this->createLiveComponent('Panel:CommandPalette', ['scope' => 'panel'], $this->client)->actingAs(self::user());

        $palette->set('query', 'produkty');
        self::assertStringNotContainsString('/admin/products', $palette->render()->toString());

        $palette->set('query', 'zadania');
        self::assertStringContainsString('/tasks', $palette->render()->toString());
    }

    /** @param array<string, mixed>|null $body */
    private function json(string $method, string $uri, ?array $body = null): void
    {
        $this->client->request($method, $uri, server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], content: $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR));
    }
}
