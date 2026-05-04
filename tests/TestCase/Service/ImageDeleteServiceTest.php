<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageDeleteService;
use Cake\Core\Configure;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ImageDeleteServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    private ?string $storageRoot = null;

    private array $previousConfig = [];

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->previousConfig['Images.storageRoot'] = Configure::read('Images.storageRoot');

        $this->storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'racerhistory-image-delete-'
            . uniqid('', true)
            . DIRECTORY_SEPARATOR;

        mkdir($this->storageRoot, 0775, true);

        Configure::write('Images.storageRoot', $this->storageRoot);

        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $subdir = str_replace('/', DIRECTORY_SEPARATOR, (string)$image->storage_subdir);
        $subdirPath = $this->storageRoot . $subdir . DIRECTORY_SEPARATOR;
        mkdir($subdirPath, 0775, true);

        file_put_contents($subdirPath . (string)$image->filename, 'ORIGINAL');

        $image = $images->patchEntity($image, [
            'variants' => json_encode([
                'thumb' => [
                    'file' => 'seed-thumb.webp',
                    'width' => 150,
                    'height' => 150,
                    'mime' => 'image/webp',
                ],
            ]),
        ], ['validate' => false]);
        $images->saveOrFail($image);

        file_put_contents($subdirPath . 'seed-thumb.webp', 'THUMB');
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        if ($this->previousConfig) {
            Configure::write('Images.storageRoot', $this->previousConfig['Images.storageRoot']);
        }

        if ($this->storageRoot && is_dir($this->storageRoot)) {
            $this->deleteDirRecursive($this->storageRoot);
        }

        parent::tearDown();
    }

    /**
     * Tests delete image by id removes db rows and files and prunes tags.
     */
    public function testDeleteImageByIdRemovesDbRowsAndFilesAndPrunesTags(): void
    {
        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $subdirPath = $this->storageRoot
            . str_replace('/', DIRECTORY_SEPARATOR, (string)$image->storage_subdir)
            . DIRECTORY_SEPARATOR;

        $this->assertFileExists($subdirPath . (string)$image->filename);
        $this->assertFileExists($subdirPath . 'seed-thumb.webp');

        $service = new ImageDeleteService();
        $result = $service->deleteImageById(1);

        $this->assertTrue((bool)($result['deleted'] ?? false));

        $this->assertFileDoesNotExist($subdirPath . (string)$image->filename);
        $this->assertFileDoesNotExist($subdirPath . 'seed-thumb.webp');

        $this->expectException(RecordNotFoundException::class);
        $images->get(1);
    }

    /**
     * Tests bulk delete images deletes join rows and prunes all orphan tags.
     */
    public function testBulkDeleteImagesDeletesJoinRowsAndPrunesAllOrphanTags(): void
    {
        $service = new ImageDeleteService();
        $service->bulkDeleteImages([1]);

        $imagesImageTags = TableRegistry::getTableLocator()->get('ImagesImageTags');
        $this->assertSame(0, $imagesImageTags->find()->where(['image_id' => 1])->count());

        $imageTags = TableRegistry::getTableLocator()->get('ImageTags');
        $this->assertSame(0, $imageTags->find()->count());
    }

    /**
     * Runs the delete dir recursive routine.
     *
     * @param string $dir
     */
    private function deleteDirRecursive(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirRecursive($path);
                continue;
            }
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
