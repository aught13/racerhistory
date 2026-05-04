<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageBrowseService;
use Cake\TestSuite\TestCase;

class ImageBrowseServiceTest extends TestCase
{
    /**
     * @var array
     */
    public array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    /**
     * Tests browse returns payload.
     */
    public function testBrowseReturnsPayload(): void
    {
        $service = new ImageBrowseService();
        $payload = $service->browse();

        $this->assertTrue($payload['success'] ?? false);
        $this->assertIsArray($payload['images'] ?? null);

        if (!empty($payload['images'])) {
            $first = reset($payload['images']);
            $this->assertIsArray($first);
            $this->assertArrayHasKey('id', $first);
            $this->assertArrayHasKey('url', $first);
            $this->assertArrayHasKey('thumbnail_url', $first);
            $this->assertArrayHasKey('original_name', $first);
            $this->assertArrayHasKey('tags', $first);
        }
    }

    /**
     * Tests browse with tag filter.
     */
    public function testBrowseWithTagFilter(): void
    {
        $service = new ImageBrowseService();
        $payload = $service->browse('roster', 50);

        $this->assertTrue($payload['success'] ?? false);
        $this->assertIsArray($payload['images'] ?? null);
        $this->assertNotEmpty($payload['images'] ?? []);

        $first = reset($payload['images']);
        $this->assertContains('Roster', $first['tags'] ?? []);
    }

    /**
     * Tests browse clamps limit to maximum.
     */
    public function testBrowseClampsLimitToMaximum(): void
    {
        $service = new ImageBrowseService();
        $payload = $service->browse(null, 99999);

        $this->assertTrue($payload['success'] ?? false);
        $this->assertLessThanOrEqual(100, count($payload['images'] ?? []));
    }
}
