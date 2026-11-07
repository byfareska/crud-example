<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use App\Entity\Supplier;
use Byfareska\Crud\Files\FileReference;
use Byfareska\Crud\Files\FileStorages;
use Byfareska\Crud\Files\FileUsage;
use Byfareska\Crud\Files\FileUsages;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Files of the panel (byfareska_crud.files): an upload to the signed address of a field, the logo of a supplier in a
 * column, the gallery of a product as its ProductImage items, images in its description (rich text) - and the index
 * of usages of them once saved.
 */
final class FilesTest extends PanelTestCase
{
    public function testOnlyAFieldOfThePageUploads(): void
    {
        $this->client->loginUser(self::admin());
        $url = $this->uploadUrl('product');

        $this->client->request('POST', '/admin/_files?storage=public', files: ['file' => $this->png()], server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', $url, files: ['file' => $this->png()]);
        self::assertResponseStatusCodeSame(400);

        $this->client->request('POST', $url, files: ['file' => $this->png('notes.txt', 'Not an image')], server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testAProductWithItsGalleryAndImagesInItsDescription(): void
    {
        $this->client->disableReboot();
        $this->client->loginUser(self::admin());
        $photo = $this->upload($this->uploadUrl('product'));
        $inText = $this->upload($this->uploadUrl('product', 'description'));
        self::assertStringStartsWith('public://', $photo['ref']);
        self::assertSame('kubek', $photo['alt'], 'Its alternative text until one is typed: the name it came with.');
        self::assertStringStartsWith('/uploads/', $inText['url']);

        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $form->submitForm(['product' => [
            'name' => 'Kubek z galerią',
            'sku' => 'MUG-GALLERY',
            'price' => '19.99',
            'stock' => '3',
            'images' => json_encode([['ref' => $photo['ref'], 'sig' => $photo['sig'], 'meta' => ['alt' => 'Kubek']]]),
            'description' => sprintf('<p>Opis <img src="%s" alt=""></p>', $inText['url']),
        ]], 'save');

        $this->entities()->clear();
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-GALLERY']);
        self::assertNotNull($product);
        self::assertCount(1, $product->getImages());
        self::assertSame($photo['ref'], $product->getImages()->first()->file);
        self::assertSame('Kubek', $product->getImages()->first()->alt);
        self::assertStringContainsString($inText['url'], (string) $product->description);

        self::assertSame([['product', (string) $product->id, 'images']], $this->usages($photo['ref']));
        self::assertSame([['product', (string) $product->id, 'description']], $this->usages($inText['ref']));
    }

    public function testAnImageHasItsSizesAndTheTextShowsOne(): void
    {
        $this->client->disableReboot();
        $this->client->loginUser(self::admin());
        $image = $this->upload($this->uploadUrl('product', 'description'));
        $reference = FileReference::parse($image['ref']);

        // Made on upload, in the storage of the image.
        $medium = '/uploads/_variants/medium/' . $reference->path . '.webp';
        self::assertSame($medium, $image['sizes']['medium']['url']);
        self::assertSame('/uploads/_variants/preview/' . $reference->path, $image['preview']);
        $storage = static::getContainer()->get(FileStorages::class)->storage('public');
        foreach (['thumbnail', 'preview', 'medium', 'large'] as $size) {
            self::assertTrue($storage->exists(sprintf('_variants/%s/%s', $size, $reference->path)
                . (in_array($size, ['medium', 'large'], true) ? '.webp' : '')), $size);
        }

        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'product'], $this->client)->actingAs(self::admin());
        $form->submitForm(['product' => [
            'name' => 'Kubek w rozmiarze',
            'sku' => 'MUG-SIZED',
            'price' => '19.99',
            'stock' => '3',
            'description' => sprintf('<p><img src="%s" alt=""></p>', $medium),
        ]], 'save');

        $this->entities()->clear();
        $product = $this->entities()->getRepository(Product::class)->findOneBy(['sku' => 'MUG-SIZED']);
        self::assertNotNull($product);
        // The text shows a size; the image is what it uses.
        self::assertSame([['product', (string) $product->id, 'description']], $this->usages($image['ref']));

        // The editor knows the sizes of the image it shows.
        $crawler = $this->client->request('GET', sprintf('/admin/products/%d/edit', $product->id));
        self::assertResponseIsSuccessful();
        $sizes = json_decode(
            (string) $crawler->filter('[data-rich-text-sizes-value]')->attr('data-rich-text-sizes-value'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        self::assertSame('medium', $sizes['initial']);
        self::assertSame(['large', 'medium'], array_column($sizes['choices'], 'name'));
        self::assertSame($image['url'], $sizes['images'][0]['url']);
    }

    public function testAFileOfAnotherIsNotTakenWithoutItsSignature(): void
    {
        $this->client->disableReboot();
        $this->client->loginUser(self::admin());
        $logo = $this->upload($this->uploadUrl('supplier', 'logo', '/suppliers/new'));
        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'supplier'], $this->client)->actingAs(self::admin());

        $errors = self::submitInvalid($form, ['supplier' => [
            'name' => 'Bez podpisu',
            'logo' => json_encode([['ref' => $logo['ref']]]),
        ]]);

        self::assertStringContainsString(htmlspecialchars(static::getContainer()->get('translator')->trans('file.field.invalid', locale: 'pl')), $errors);
        self::assertNull($this->entities()->getRepository(Supplier::class)->findOneBy(['name' => 'Bez podpisu']));
    }

    public function testTheAttachmentsOfATaskInItsFileOpenedBySignedAddresses(): void
    {
        $this->client->disableReboot();
        $this->client->loginUser(self::user());
        $pdf = $this->upload(
            $this->uploadUrl('create_task_dto', 'attachments', '/tasks/new'),
            $this->png('umowa.pdf', "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n"),
        );
        self::assertStringStartsWith('private://', $pdf['ref']);
        self::assertStringContainsString('_hash=', $pdf['url'], 'A private file is opened by a signed address.');

        $form = $this->createLiveComponent('Crud:Form', ['resource' => 'task'], $this->client)->actingAs(self::user());
        $form->submitForm(['create_task_dto' => [
            'title' => 'Podpisać umowę',
            'priority' => 'normal',
            'attachments' => json_encode([['ref' => $pdf['ref'], 'sig' => $pdf['sig']]]),
        ]], 'save');

        $file = static::getContainer()->getParameter('kernel.project_dir') . '/var/tasks_test.json';
        $tasks = json_decode((string) file_get_contents($file), true);
        $task = array_values(array_filter($tasks, static fn (array $task): bool => $task['title'] === 'Podpisać umowę'))[0] ?? null;
        self::assertIsArray($task);
        self::assertSame([$pdf['ref']], $task['attachments']);
        self::assertSame([['task', $task['id'], 'attachments']], $this->usages($pdf['ref']));

        $crawler = $this->client->request('GET', '/tasks/' . $task['id']);
        self::assertResponseIsSuccessful();
        $link = $crawler->filter('a[href*="/_files/download"]')->attr('href');
        self::assertIsString($link);
        $this->client->request('GET', $link);
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/pdf');
        self::assertStringStartsWith('%PDF', (string) $this->client->getInternalResponse()->getContent());

        // The live component of the test lets the client throw what the kernel throws: here it answers.
        $this->client->catchExceptions(true);
        $this->client->request('GET', preg_replace('/_hash=[^&]+/', '_hash=forged', $link));
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * The signed address a field of the form uploads to.
     */
    private function uploadUrl(string $resource, string $field = 'images', ?string $page = null): string
    {
        $crawler = $this->client->request('GET', $page ?? '/admin/products/new');
        self::assertResponseIsSuccessful();
        $attribute = $field === 'description' ? 'data-rich-text-upload-url-value' : 'data-file-field-upload-url-value';
        $url = $crawler->filter(sprintf('[id="%s_%s"], [id="%1$s_%2$s_editor"]', $resource, $field))
            ->closest(sprintf('[%s]', $attribute))
            ?->attr($attribute);
        self::assertIsString($url);

        return $url;
    }

    /**
     * @return array{ref: string, sig: string, url: string, preview: string, alt: string,
     *     sizes: array<string, array{url: string}>}
     */
    private function upload(string $url, ?UploadedFile $file = null): array
    {
        $this->client->request('POST', $url, files: ['file' => $file ?? $this->png()], server: [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ]);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return list<array{string, string, string}> */
    private function usages(string $reference): array
    {
        $usages = static::getContainer()->get(FileUsages::class)->of(FileReference::parse($reference));

        return array_map(static fn (FileUsage $usage): array => [$usage->resource, $usage->id, $usage->field], $usages);
    }

    private function png(string $name = 'kubek.png', ?string $contents = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        // A PNG of one pixel: what it is is read from its bytes.
        file_put_contents($path, $contents ?? base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ));

        return new UploadedFile($path, $name, null, null, true);
    }
}
