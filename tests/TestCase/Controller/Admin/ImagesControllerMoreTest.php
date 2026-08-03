<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Throwable;

class ImagesControllerMoreTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.Persons',
        'app.TeamSeasonRosters',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
    ];

    private string $storageRoot;

    /**
     * @var int[]
     */
    private array $createdImageIds = [];

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // These admin actions currently read/write under webroot for editing flows.
        $this->storageRoot = WWW_ROOT . 'img' . DS . 'storage' . DS;
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        // Best-effort cleanup for created images (files + variants).
        if ($this->createdImageIds) {
            $images = TableRegistry::getTableLocator()->get('Images');
            foreach ($this->createdImageIds as $id) {
                try {
                    $record = $images->get($id);
                } catch (Throwable) {
                    continue;
                }

                $storagePath = $record->get('storage_path');
                if (is_string($storagePath) && $storagePath !== '') {
                    $sanitized = str_replace(['..', '\\'], '', $storagePath);
                    $full = $this->storageRoot . ltrim($sanitized, '/\\');
                    if (is_file($full)) {
                        $this->safeUnlink($full);
                    }

                    $variantsRaw = $record->get('variants');
                    $variants = is_string($variantsRaw) ? json_decode($variantsRaw, true) : $variantsRaw;
                    if (is_array($variants)) {
                        foreach ($variants as $meta) {
                            $file = is_array($meta) ? ($meta['file'] ?? null) : null;
                            if (!$file) {
                                continue;
                            }
                            $vf = dirname($full) . DS . $file;
                            if (is_file($vf)) {
                                $this->safeUnlink($vf);
                            }
                        }
                    }
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Tests upload without file returns json error.
     */
    public function testUploadWithoutFileReturnsJsonError(): void
    {
        $this->mockIdentity();

        $this->post('/admin/images/upload', []);

        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['success'] ?? true);
        $this->assertSame('No file uploaded', $json['error'] ?? null);
    }

    /**
     * Tests upload form page renders the Stimulus upload controller.
     */
    public function testUploadFormPageRendersStimulusController(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/upload-form');

        $this->assertResponseOk();
        $this->assertResponseContains('data-controller="image-upload"');
        $this->assertResponseContains('data-image-upload-target="fileInput"');
        $this->assertResponseContains('data-image-upload-target="previewContainer"');
    }

    /**
     * Tests manipulate page eagerly loads its source image.
     */
    public function testManipulatePageEagerLoadsSourceImage(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/manipulate/1');

        $this->assertResponseOk();
        $this->assertResponseContains('loading="eager"');
        $this->assertResponseContains('fetchpriority="high"');
        $this->assertResponseContains('data-controller="admin-image-manipulate"');
        $this->assertResponseContains('data-action="click->admin-image-manipulate#setAspectRatio"');
    }

    /**
     * Tests bulk upload form renders Stimulus bulk upload controller.
     */
    public function testBulkUploadFormPageRendersStimulusController(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/bulk-upload-form');

        $this->assertResponseOk();
        $this->assertResponseContains('data-controller="admin-image-bulk-upload"');
        $this->assertResponseContains('data-admin-image-bulk-upload-target="uploadsInput"');
        $this->assertResponseContains('data-action="click->admin-image-bulk-upload#uploadAll"');
        $this->assertResponseContains('tag-modal-hidden-inputs');
    }

    /**
     * Tests crop thumb page renders Stimulus crop controller.
     */
    public function testCropThumbPageRendersStimulusController(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/crop-thumb/1');

        $this->assertResponseOk();
        $this->assertResponseContains('data-controller="admin-image-crop-thumb"');
        $this->assertResponseContains('data-admin-image-crop-thumb-target="container"');
        $this->assertResponseContains('data-action="click->admin-image-crop-thumb#reset"');
    }

    /**
     * Tests bulk upload with invalid payload entry returns error result.
     */
    public function testBulkUploadWithInvalidPayloadEntryReturnsErrorResult(): void
    {
        $this->mockIdentity();

        $this->post('/admin/images/bulk-upload', [
            'uploads' => ['not-an-upload'],
        ]);

        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertIsArray($json['results'] ?? null);
        $this->assertSame(false, $json['results'][0]['success'] ?? null);
        $this->assertSame('Invalid upload payload', $json['results'][0]['error'] ?? null);
    }

    /**
     * Tests bulk upload with string tags and context succeeds.
     */
    public function testBulkUploadWithStringTagsAndContextSucceeds(): void
    {
        $this->mockIdentity();

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        $pngData = $this->tinyPngBytes();
        file_put_contents($tmp, $pngData);

        $this->post('/admin/images/bulk-upload', [
            'uploads' => [[
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ]],
            'tags' => 'tag-one, tag-two',
            'context' => 'My Context',
        ]);

        $this->assertResponseOk();
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertCount(1, $json['results'] ?? []);

        $imageId = (int)($json['results'][0]['image']['id'] ?? 0);
        $this->assertGreaterThan(0, $imageId);
        $this->createdImageIds[] = $imageId;
    }

    /**
     * Tests edit post with no fields redirects.
     */
    public function testEditPostWithNoFieldsRedirects(): void
    {
        $this->mockIdentity();

        $this->post('/admin/images/edit/1', []);

        $this->assertRedirectContains('/admin/images/edit/1');
    }

    /**
     * Tests edit post updates fields and redirects.
     */
    public function testEditPostUpdatesFieldsAndRedirects(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/edit/1', [
            'original_name' => 'renamed.png',
            'status' => 'inactive',
        ]);

        $this->assertRedirectContains('/admin/images/edit/1');

        $images = $this->getTableLocator()->get('Images');
        $updated = $images->get(1);
        $this->assertSame('renamed.png', $updated->get('original_name'));
        $this->assertSame('inactive', $updated->get('status'));
    }

    /**
     * Tests manipulate post missing file redirects to index.
     */
    public function testManipulatePostMissingFileRedirectsToIndex(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/manipulate/1', [
            'rotate' => '10',
            'crop' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1],
        ]);

        $this->assertRedirectContains('/admin/images');
    }

    /**
     * Tests manipulate post no manipulations redirects back to form.
     */
    public function testManipulatePostNoManipulationsRedirectsBackToForm(): void
    {
        $this->mockIdentity();

        $fullPath = $this->writeFixtureImageFile(1, $this->tinyPngBytes());

        $this->post('/admin/images/manipulate/1', []);
        $this->assertRedirectContains('/admin/images/manipulate/1');

        $this->safeUnlink($fullPath);
    }

    /**
     * Tests crop thumb missing file redirects to edit.
     */
    public function testCropThumbMissingFileRedirectsToEdit(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/crop-thumb/1', [
            'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10],
        ]);

        $this->assertRedirectContains('/admin/images/edit/1');
    }

    /**
     * Tests crop thumb invalid crop redirects back to crop.
     */
    public function testCropThumbInvalidCropRedirectsBackToCrop(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $fullPath = $this->writeFixtureImageFile(1, $this->tinyPngBytes());

        $this->post('/admin/images/crop-thumb/1', [
            'crop' => ['x' => 0, 'y' => 0],
        ]);

        $this->assertRedirectContains('/admin/images/crop-thumb/1');

        $this->safeUnlink($fullPath);
    }

    /**
     * Tests crop hero missing file redirects to edit.
     */
    public function testCropHeroMissingFileRedirectsToEdit(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/crop-hero/1', [
            'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10],
        ]);

        $this->assertRedirectContains('/admin/images/edit/1');
    }

    /**
     * Tests crop hero invalid crop redirects back to crop.
     */
    public function testCropHeroInvalidCropRedirectsBackToCrop(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $fullPath = $this->writeFixtureImageFile(1, $this->tinyPngBytes());

        $this->post('/admin/images/crop-hero/1', [
            'crop' => ['x' => 0, 'y' => 0],
        ]);

        $this->assertRedirectContains('/admin/images/crop-hero/1');

        $this->safeUnlink($fullPath);
    }

    /**
     * Tests tags post applies and redirects.
     */
    public function testTagsPostAppliesAndRedirects(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/tags/1', [
            'tags' => 'freeform-tag',
        ]);

        $this->assertRedirectContains('/admin/images/tags/1');

        // Ensure a tag row exists and is linked.
        $tags = $this->getTableLocator()->get('ImageTags');
        $created = $tags->find()->where(['slug' => 'freeform-tag'])->first();
        $this->assertNotNull($created);

        $images = $this->getTableLocator()->get('Images');
        $image = $images->get(1, contain: ['ImageTags']);
        $slugs = array_map(fn($t) => $t->slug, $image->image_tags ?? []);
        $this->assertContains('freeform-tag', $slugs);
    }

    /**
     * Tests bulk delete deletes one image.
     */
    public function testBulkDeleteDeletesOneImage(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();

        $this->post('/admin/images/bulk-delete', [
            'ids' => [1],
        ]);

        $this->assertRedirectContains('/admin/images');

        $images = $this->getTableLocator()->get('Images');
        $this->expectException(RecordNotFoundException::class);
        $images->get(1);
    }

    /**
     * Tests rosters endpoint with no person id returns empty list.
     */
    public function testRostersEndpointWithNoPersonIdReturnsEmptyList(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/rosters');
        $this->assertResponseOk();

        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertSame([], $json['rosters'] ?? null);
    }

    /**
     * Tests rosters endpoint returns rows.
     */
    public function testRostersEndpointReturnsRows(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/rosters?person_id=1');
        $this->assertResponseOk();

        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertNotEmpty($json['rosters'] ?? []);

        $row = $json['rosters'][0];
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('label', $row);
        $this->assertStringContainsString('John', $row['label']);
    }

    /**
     * Tests persons endpoint with empty query returns empty list.
     */
    public function testPersonsEndpointWithEmptyQueryReturnsEmptyList(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/persons?q=');
        $this->assertResponseOk();

        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertSame([], $json['persons'] ?? null);
    }

    /**
     * Tests persons endpoint returns matches.
     */
    public function testPersonsEndpointReturnsMatches(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/persons?q=John');
        $this->assertResponseOk();

        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertNotEmpty($json['persons'] ?? []);

        $first = $json['persons'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertSame(1, (int)$first['id']);
        $this->assertStringContainsString('John', $first['label']);
    }

    /**
     * Tests admin serve preserves legacy sizing query when delegating to public serve.
     */
    public function testServeLegacySizingQueryDelegatesToPublicServe(): void
    {
        $this->mockIdentity();

        $this->get('/admin/images/serve/1?w=300&h=300&fit=cover');
        $this->assertRedirectContains('/images/serve/1');

        $location = $this->_response->getHeaderLine('Location');
        $this->assertStringContainsString('w=300', $location);
        $this->assertStringContainsString('h=300', $location);
        $this->assertStringContainsString('fit=cover', $location);
    }

    /**
     * Runs the tiny png bytes routine.
     */
    private function tinyPngBytes(): string
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=';

        return (string)base64_decode($b64);
    }

    /**
     * Runs the write fixture image file routine.
     *
     * @param int $id
     * @param string $bytes
     */
    private function writeFixtureImageFile(int $id, string $bytes): string
    {
        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get($id);
        $storagePath = (string)($image->storage_path ?? '');
        $this->assertNotSame('', $storagePath);

        $dir = $this->storageRoot . dirname($storagePath) . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $fullPath = $this->storageRoot . $storagePath;
        file_put_contents($fullPath, $bytes);

        return $fullPath;
    }

    /**
     * Runs the safe unlink routine.
     *
     * @param string $path
     */
    private function safeUnlink(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        set_error_handler(static fn() => true);
        unlink($path);
        restore_error_handler();
    }
}
