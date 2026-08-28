<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BlogContentService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class BlogContentServiceTest extends TestCase
{
    public array $fixtures = ['app.Images'];

    private BlogContentService $service;

    /**
     * Configure a credited fixture image for each test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        $image = $imagesTable->get(1);
        $image->photo_credit = 'Murray State Athletics';
        $imagesTable->saveOrFail($image);
        $this->service = new BlogContentService($imagesTable);
    }

    /**
     * Legacy inline image URLs should resolve their stored photo credit.
     */
    public function testRenderWithPhotoCreditsResolvesStoredImagePath(): void
    {
        $image = TableRegistry::getTableLocator()->get('Images')->get(1);
        $body = '<p>Story text</p><picture><img src="/img/storage/' . $image->storage_path . '"></picture>';

        $result = $this->service->renderWithPhotoCredits($body);

        $this->assertStringContainsString('blog-image-credit', $result);
        $this->assertStringContainsString('Photo: Murray State Athletics', $result);
        $this->assertStringContainsString((string)$image->storage_path, $result);
    }

    /**
     * New inline image markup should resolve credits using its image id.
     */
    public function testRenderWithPhotoCreditsResolvesImageId(): void
    {
        $body = '<p>Story text</p><img data-image-id="1" src="/img/storage/unknown.jpg">';

        $result = $this->service->renderWithPhotoCredits($body);

        $this->assertStringContainsString('data-image-id="1"', $result);
        $this->assertStringContainsString('Photo: Murray State Athletics', $result);
    }

    /**
     * Unknown images should remain unchanged rather than being rewritten.
     */
    public function testRenderWithPhotoCreditsLeavesUnknownImagesUnchanged(): void
    {
        $body = '<p>Story text</p><img src="https://example.com/photo.jpg">';

        $this->assertSame($body, $this->service->renderWithPhotoCredits($body));
    }

    /**
     * Rendering the same body twice should not duplicate its credit caption.
     */
    public function testRenderWithPhotoCreditsIsIdempotent(): void
    {
        $image = TableRegistry::getTableLocator()->get('Images')->get(1);
        $body = '<picture><img src="/img/storage/' . $image->storage_path . '"></picture>';
        $rendered = $this->service->renderWithPhotoCredits($body);

        $this->assertSame($rendered, $this->service->renderWithPhotoCredits($rendered));
        $this->assertSame(1, substr_count($rendered, 'blog-image-credit__label'));
    }
}
