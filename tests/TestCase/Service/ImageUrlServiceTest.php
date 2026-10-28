<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Image;
use App\Model\Table\ImagesTable;
use App\Service\ImageUrlService;
use Cake\TestSuite\TestCase;

class ImageUrlServiceTest extends TestCase
{
    protected array $fixtures = ['app.Images'];

    private ImageUrlService $service;

    /**
     * Initialize the image URL service with fixture-backed images.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $imagesTable = $this->getTableLocator()->get('Images');
        $this->assertInstanceOf(ImagesTable::class, $imagesTable);
        $this->service = new ImageUrlService($imagesTable);
    }

    /**
     * Verify the service returns an image when the ID exists.
     *
     * @return void
     */
    public function testFindImageByIdReturnsImageWhenFound(): void
    {
        $image = $this->service->findImageById(1);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame(1, $image->id);
    }

    /**
     * Verify the service returns null when the ID is missing.
     *
     * @return void
     */
    public function testFindImageByIdReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->service->findImageById(999999));
    }
}
