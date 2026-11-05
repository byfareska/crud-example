<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Every page of the panel in the three themes of the bundle (byfareska_crud.theme = %env(CRUD_THEME)%), each drawn
 * with its own markup: Bootstrap's components, or native <dialog> and <details> (Tailwind, daisyUI).
 */
final class ThemesTest extends PanelTestCase
{
    private ?string $previous = null;

    protected function tearDown(): void
    {
        unset($_SERVER['CRUD_ADMIN_THEME'], $_ENV['CRUD_ADMIN_THEME']);
        $this->theme($this->previous);
        parent::tearDown();
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function themes(): iterable
    {
        yield 'bootstrap5' => ['bootstrap5', '.modal#command-palette', '.offcanvas#crud-preview-product'];
        yield 'tailwind' => ['tailwind', 'dialog#command-palette', 'dialog#crud-preview-product'];
        yield 'daisyui' => ['daisyui', 'dialog.modal#command-palette', 'dialog.modal#crud-preview-product'];
    }

    #[DataProvider('themes')]
    public function testEveryPageInTheTheme(string $theme, string $palette, string $preview): void
    {
        $this->previous = $_SERVER['CRUD_THEME'] ?? null;
        $this->theme($theme);
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-002']);
        $client = $this->client;
        $client->loginUser(self::admin());

        $pages = ['/', '/tasks', '/tasks/new', '/suppliers', '/suppliers/new', '/bundles', '/admin', '/admin/products', '/admin/products/new', '/admin/products/' . $product->id, '/admin/products/' . $product->id . '/edit', '/admin/categories'];
        // A page of #[AsCrudPage] and a page of the application's with #[MapCrud]: the frame of a record of the theme.
        $pages[] = '/admin/products/' . $product->id . '/stats';
        $pages[] = '/admin/categories/' . $product->category->id . '/summary';
        foreach ($pages as $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            self::assertSelectorExists($palette, $path);
        }

        $crawler = $client->request('GET', '/admin/products');
        self::assertSelectorExists($preview);
        self::assertStringContainsString('panel_' . $theme, $crawler->filter('head')->html());

        // Signed out: the sign-in page of the theme.
        $client->restart();
        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name=_username]');
    }

    public function testTheColourThemesOfDaisyUi(): void
    {
        $this->previous = $_SERVER['CRUD_THEME'] ?? null;
        $this->theme('daisyui');
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/admin/products/new');
        self::assertResponseIsSuccessful();
        // Every colour theme of byfareska_crud.color_themes in the picker, "system" first; remembered under a key of its own.
        $picker = $crawler->filter('[data-controller~=theme]');
        self::assertSame('panel-theme-daisyui', $picker->attr('data-theme-storage-key-value'));
        self::assertSame('auto', $crawler->filter('[data-theme-target=option]')->first()->attr('data-theme-theme-param'));
        self::assertCount(36, $crawler->filter('[data-theme-target=option]'));
        self::assertSelectorExists('[data-theme-theme-param=dracula] [data-theme=dracula]');
        self::assertContains('dracula', json_decode((string) $picker->attr('data-theme-dark-value'), true));
        self::assertStringContainsString('panel-theme-daisyui', $crawler->filter('head script')->text());
        // The form in daisyUI's classes (daisyui_5_layout).
        self::assertSelectorExists('form input.input');
        self::assertSelectorExists('form select.select');
    }

    public function testEachPanelInItsTheme(): void
    {
        // byfareska_crud.scopes.admin.theme of the example: CRUD_ADMIN_THEME, else CRUD_THEME.
        $this->previous = $_SERVER['CRUD_THEME'] ?? null;
        $_SERVER['CRUD_ADMIN_THEME'] = $_ENV['CRUD_ADMIN_THEME'] = 'tailwind';
        $this->theme('bootstrap5');
        $this->client->loginUser(self::admin());

        $crawler = $this->client->request('GET', '/tasks');
        self::assertSelectorExists('.modal#command-palette');
        self::assertStringContainsString('panel_bootstrap5', $crawler->filter('head')->html());

        $crawler = $this->client->request('GET', '/admin/products');
        self::assertSelectorExists('dialog#command-palette');
        self::assertStringContainsString('panel_tailwind', $crawler->filter('head')->html());

        // The address of the live endpoint names no panel: the component says which.
        $render = fn (string $name, array $props): string => $this->createLiveComponent($name, $props, $this->client)->actingAs(self::admin())->render()->toString();
        self::assertStringNotContainsString('offcanvas', $render('Crud:List', ['resource' => 'product']));
        self::assertStringContainsString('offcanvas', $render('Crud:List', ['resource' => 'task']));
        self::assertStringNotContainsString('form-control', $render('Crud:Form', ['resource' => 'product']));
        self::assertStringContainsString('form-control', $render('Crud:Form', ['resource' => 'task']));
        self::assertStringNotContainsString('modal-content', $render('Panel:CommandPalette', ['scope' => 'admin']));
        self::assertStringContainsString('modal-content', $render('Panel:CommandPalette', ['scope' => 'panel']));
    }

    /** A kernel of its own, with the theme in its environment. */
    private function theme(?string $theme): void
    {
        self::ensureKernelShutdown();
        if ($theme === null) {
            unset($_SERVER['CRUD_THEME'], $_ENV['CRUD_THEME']);
        } else {
            $_SERVER['CRUD_THEME'] = $_ENV['CRUD_THEME'] = $theme;
        }
        $this->client = static::createClient();
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'pl');
    }
}
