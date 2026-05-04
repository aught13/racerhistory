<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageTagService;
use Cake\TestSuite\TestCase;

class ImageTagServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    private ImageTagService $service;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageTagService();
    }

    /**
     * Tests get images by all tags.
     */
    public function testGetImagesByAllTags(): void
    {
        $images = $this->service->getImagesByAllTags(['person-1', 'roster'], 10);
        $this->assertNotEmpty($images);
        $this->assertSame(1, (int)$images[0]->id);
    }

    /**
     * Tests ensure tags creates missing.
     */
    public function testEnsureTagsCreatesMissing(): void
    {
        $tags = $this->service->ensureTags(['new-tag']);
        $this->assertNotEmpty($tags);
        $this->assertSame('new-tag', $tags[0]->slug);
    }
}
