<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageEditService;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ImageEditServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
    ];

    private ?string $storageRoot = null;

    private array $previousConfig = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->previousConfig['Images.storageRoot'] = Configure::read('Images.storageRoot');
        $this->previousConfig['Images.variants'] = Configure::read('Images.variants');

        $this->storageRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'racerhistory-image-edit-'
            . uniqid('', true)
            . DIRECTORY_SEPARATOR;

        mkdir($this->storageRoot, 0775, true);

        Configure::write('Images.storageRoot', $this->storageRoot);
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
            'medium' => ['maxWidth' => 800, 'format' => 'webp'],
            'webp' => ['format' => 'webp'],
        ]);

        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $subdirPath = $this->storageRoot
            . str_replace('/', DIRECTORY_SEPARATOR, (string)$image->storage_subdir)
            . DIRECTORY_SEPARATOR;

        mkdir($subdirPath, 0775, true);

        file_put_contents($subdirPath . (string)$image->filename, 'OLD');

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

        file_put_contents($subdirPath . 'seed-thumb.webp', 'OLDTHUMB');
    }

    public function tearDown(): void
    {
        if ($this->previousConfig) {
            Configure::write('Images.storageRoot', $this->previousConfig['Images.storageRoot']);
            Configure::write('Images.variants', $this->previousConfig['Images.variants']);
        }

        if ($this->storageRoot && is_dir($this->storageRoot)) {
            $this->deleteDirRecursive($this->storageRoot);
        }

        parent::tearDown();
    }

    public function testManipulateImageApplyReusesVariantFilenamesAndUpdatesMetadata(): void
    {
        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $processor = $this->createFakeProcessor();
        $service = new ImageEditService($processor, null);
        $result = $service->manipulateImage($images, $image, ['rotate' => 90], 'apply', null);

        $this->assertTrue((bool)($result['success'] ?? false));
        $this->assertSame('applied', (string)($result['status'] ?? ''));

        $reloaded = $images->get(1);

        $subdirPath = $this->storageRoot
            . str_replace('/', DIRECTORY_SEPARATOR, (string)$reloaded->storage_subdir)
            . DIRECTORY_SEPARATOR;

        $this->assertSame('NEWORIGINAL', (string)file_get_contents($subdirPath . (string)$reloaded->filename));

        $variants = $reloaded->variants;
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }

        $this->assertIsArray($variants);
        $this->assertArrayHasKey('thumb', $variants);
        $this->assertSame('seed-thumb.webp', $variants['thumb']['file']);
        $this->assertSame('NEWVAR-thumb', (string)file_get_contents($subdirPath . 'seed-thumb.webp'));

        $this->assertSame(hash('sha256', 'NEWORIGINAL'), (string)$reloaded->hash);
        $this->assertSame(strlen('NEWORIGINAL'), (int)$reloaded->byte_size);
        $this->assertSame(100, (int)$reloaded->width);
        $this->assertSame(50, (int)$reloaded->height);
    }

    public function testCropThumbVariantUpdatesThumbFileAndHash(): void
    {
        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $processor = $this->createFakeProcessor();
        $service = new ImageEditService($processor, null);
        $result = $service->cropThumbVariant($images, $image, [
            'x' => 1,
            'y' => 2,
            'width' => 10,
            'height' => 12,
        ]);

        $this->assertTrue((bool)($result['success'] ?? false));

        $reloaded = $images->get(1);

        $subdirPath = $this->storageRoot
            . str_replace('/', DIRECTORY_SEPARATOR, (string)$reloaded->storage_subdir)
            . DIRECTORY_SEPARATOR;

        $this->assertSame('NEWVAR-thumb', (string)file_get_contents($subdirPath . 'seed-thumb.webp'));
        $this->assertSame(hash('sha256', 'NEWVAR-thumb'), (string)$reloaded->hash);
    }

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

    private function createFakeProcessor(): \App\Service\ImageProcessor
    {
        $mock = $this->getMockBuilder(\App\Service\ImageProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['manipulateExisting'])
            ->getMock();

        $mock->method('manipulateExisting')->willReturnCallback(
            static function (
                string $fileContent,
                string $mimeType,
                array $variantConfig,
                array $manipulations,
            ): array {
                $variants = [];
                foreach ($variantConfig as $name => $cfg) {
                    $format = $cfg['format'] ?? null;
                    $mime = $format === 'webp' ? 'image/webp' : $mimeType;
                    $variants[$name] = [
                        'data' => 'NEWVAR-' . $name,
                        'width' => $name === 'thumb' ? 150 : 100,
                        'height' => $name === 'thumb' ? 150 : 50,
                        'mime' => $mime,
                        'ext' => $format === 'webp' ? 'webp' : 'jpg',
                    ];
                }

                return [
                    'original' => [
                        'data' => 'NEWORIGINAL',
                        'width' => 100,
                        'height' => 50,
                        'mime' => $mimeType,
                        'ext' => 'jpg',
                    ],
                    'variants' => $variants,
                ];
            }
        );

        return $mock;
    }
}
