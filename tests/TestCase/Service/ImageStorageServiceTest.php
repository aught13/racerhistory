<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageProcessor;
use App\Service\ImageStorageService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

class ImageStorageServiceTest extends TestCase
{
    /**
     * @var array<int,string>
     */
    public array $fixtures = ['app.Images'];

    private ImageStorageService $service;
    private string $storageRoot;
    private string $legacyRoot;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = rtrim((string)Configure::read('Images.storageRoot'), DS) . DS;
        $this->legacyRoot = rtrim((string)Configure::read('Images.legacyStorageRoot'), DS) . DS;
        $this->clearDir($this->storageRoot);
        $this->clearDir($this->legacyRoot);
        $this->service = new ImageStorageService();
    }

    /**
     * Tears down the test case.
     */
    protected function tearDown(): void
    {
        $this->clearDir($this->storageRoot);
        $this->clearDir($this->legacyRoot);

        parent::tearDown();
    }

    /**
     * Tests resolve image path uses variant mime.
     */
    public function testResolveImagePathUsesVariantMime(): void
    {
        $images = $this->getTableLocator()->get('Images');
        $image = $images->get(1);
        $subdir = $image->storage_subdir;

        $baseDir = $this->storageRoot . $subdir . DS;
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0775, true);
        }

        $variantFile = 'seed-thumb.webp';
        $image->variants = [
            'thumb' => [
                'file' => $variantFile,
                'mime' => 'image/webp',
            ],
        ];

        file_put_contents($baseDir . $variantFile, 'variant-bytes');

        [$path, $mime] = $this->service->resolveImagePath($image, 'thumb');

        $this->assertSame($baseDir . $variantFile, $path);
        $this->assertSame('image/webp', $mime);
    }

    /**
     * Tests resolve image path falls back to legacy path.
     */
    public function testResolveImagePathFallsBackToLegacyPath(): void
    {
        $images = $this->getTableLocator()->get('Images');
        $image = $images->get(1);
        $subdir = $image->storage_subdir;

        $legacyDir = $this->legacyRoot . $subdir . DS;
        if (!is_dir($legacyDir)) {
            mkdir($legacyDir, 0775, true);
        }

        $legacyPath = $legacyDir . $image->filename;
        file_put_contents($legacyPath, 'legacy-bytes');

        [$path, $mime] = $this->service->resolveImagePath($image, '');

        $this->assertSame($legacyPath, $path);
        $this->assertSame($image->mime, $mime);
    }

    /**
     * Tests duplicate upload restores missing variant files.
     */
    public function testUploadRestoresMissingVariantFilesForExistingImage(): void
    {
        $previousStorageRoot = Configure::read('Images.storageRoot');
        $previousVariants = Configure::read('Images.variants');

        $tempRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'racerhistory-image-storage-'
            . uniqid('', true)
            . DIRECTORY_SEPARATOR;
        mkdir($tempRoot, 0775, true);

        Configure::write('Images.storageRoot', $tempRoot);
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
            'medium' => ['maxWidth' => 800, 'format' => 'webp'],
        ]);

        $uploadPath = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($uploadPath, $pngData);

        try {
            $images = $this->getTableLocator()->get('Images');
            $image = $images->get(1);
            $subdir = (string)$image->storage_subdir;
            $baseDir = $tempRoot . str_replace('/', DIRECTORY_SEPARATOR, $subdir) . DIRECTORY_SEPARATOR;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0775, true);
            }

            $originalData = 'ORIGINAL-BYTES';
            $thumbData = 'THUMB-BYTES';
            file_put_contents($baseDir . (string)$image->filename, $originalData);

            $image = $images->patchEntity($image, [
                'storage_subdir' => $subdir,
                'storage_path' => $subdir . '/' . (string)$image->filename,
                'byte_size' => strlen($originalData),
                'width' => 100,
                'height' => 50,
                'hash' => hash('sha256', $originalData),
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

            $processor = $this->getMockBuilder(ImageProcessor::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['process'])
                ->getMock();

            $processor->expects($this->once())->method('process')->willReturn([
                'original' => [
                    'data' => $originalData,
                    'width' => 100,
                    'height' => 50,
                    'mime' => 'image/png',
                    'ext' => 'png',
                ],
                'variants' => [
                    'thumb' => [
                        'data' => $thumbData,
                        'width' => 150,
                        'height' => 150,
                        'mime' => 'image/webp',
                        'ext' => 'webp',
                    ],
                    'medium' => [
                        'data' => 'MEDIUM-BYTES',
                        'width' => 100,
                        'height' => 50,
                        'mime' => 'image/webp',
                        'ext' => 'webp',
                    ],
                ],
            ]);

            $service = new ImageStorageService($processor, null);
            $uploadedFile = new UploadedFile(
                $uploadPath,
                filesize($uploadPath),
                UPLOAD_ERR_OK,
                'dot.png',
                'image/png',
            );

            $result = $service->upload($uploadedFile);

            $this->assertTrue($result['success'] ?? false, 'Duplicate upload should succeed');
            $this->assertTrue($result['existing'] ?? false, 'Duplicate upload should return existing image');
            $this->assertSame($originalData, (string)file_get_contents($baseDir . (string)$image->filename));
            $this->assertSame($thumbData, (string)file_get_contents($baseDir . 'seed-thumb.webp'));
            $this->assertSame('MEDIUM-BYTES', (string)file_get_contents($baseDir . 'seed-medium.webp'));

            $reloaded = $images->get(1);
            $variants = $reloaded->variants;
            if (is_string($variants)) {
                $variants = json_decode($variants, true);
            }

            $this->assertIsArray($variants);
            $this->assertArrayHasKey('thumb', $variants);
            $this->assertArrayHasKey('medium', $variants);
            $this->assertSame('seed-thumb.webp', $variants['thumb']['file']);
            $this->assertSame(150, $variants['thumb']['width']);
            $this->assertSame(150, $variants['thumb']['height']);
            $this->assertSame('image/webp', $variants['thumb']['mime']);
            $this->assertSame('seed-medium.webp', $variants['medium']['file']);
            $this->assertSame(100, $variants['medium']['width']);
            $this->assertSame(50, $variants['medium']['height']);
            $this->assertSame('image/webp', $variants['medium']['mime']);
        } finally {
            if (is_file($uploadPath)) {
                unlink($uploadPath);
            }

            $this->clearDir($tempRoot);
            if (is_dir($tempRoot)) {
                rmdir($tempRoot);
            }

            Configure::write('Images.storageRoot', $previousStorageRoot);
            Configure::write('Images.variants', $previousVariants);
        }
    }

    /**
     * Tests upload fails when storage directory cannot be created
     */
    public function testUploadFailsWhenRestoreStorageDirectoryCannotBeCreated(): void
    {
        $previousStorageRoot = Configure::read('Images.storageRoot');
        $previousVariants = Configure::read('Images.variants');

        $blockedRoot = tempnam(sys_get_temp_dir(), 'racerhistory-storage-block-');
        $uploadPath = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($uploadPath, $pngData);

        Configure::write('Images.storageRoot', $blockedRoot);
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
        ]);

        try {
            $images = $this->getTableLocator()->get('Images');
            $image = $images->get(1);
            $subdir = (string)$image->storage_subdir;
            $originalData = 'ORIGINAL-BYTES';

            $image = $images->patchEntity($image, [
                'storage_subdir' => $subdir,
                'storage_path' => $subdir . '/' . (string)$image->filename,
                'hash' => hash('sha256', $originalData),
                'variants' => json_encode([
                    'thumb' => [
                        'file' => 'seed-thumb.webp',
                        'mime' => 'image/webp',
                    ],
                ]),
            ], ['validate' => false]);
            $images->saveOrFail($image);

            $processor = $this->getMockBuilder(ImageProcessor::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['process'])
                ->getMock();

            $processor->expects($this->once())->method('process')->willReturn([
                'original' => [
                    'data' => $originalData,
                    'width' => 100,
                    'height' => 50,
                    'mime' => 'image/png',
                    'ext' => 'png',
                ],
                'variants' => [
                    'thumb' => [
                        'data' => 'THUMB-BYTES',
                        'width' => 150,
                        'height' => 150,
                        'mime' => 'image/webp',
                        'ext' => 'webp',
                    ],
                ],
            ]);

            $service = new ImageStorageService($processor, null);
            $uploadedFile = new UploadedFile(
                $uploadPath,
                filesize($uploadPath),
                UPLOAD_ERR_OK,
                'dot.png',
                'image/png',
            );

            $result = $service->upload($uploadedFile);

            $this->assertFalse($result['success'] ?? true);
            $this->assertStringContainsString('storage directory', (string)($result['error'] ?? ''));
        } finally {
            if ($blockedRoot !== false && is_file($blockedRoot)) {
                unlink($blockedRoot);
            }
            if (is_file($uploadPath)) {
                unlink($uploadPath);
            }

            Configure::write('Images.storageRoot', $previousStorageRoot);
            Configure::write('Images.variants', $previousVariants);
        }
    }

    /**
     * Tests upload fails when processed variant data is missing
     */
    public function testUploadFailsWhenRestoreVariantDataMissing(): void
    {
        $previousStorageRoot = Configure::read('Images.storageRoot');
        $previousVariants = Configure::read('Images.variants');

        $tempRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'racerhistory-image-storage-'
            . uniqid('', true)
            . DIRECTORY_SEPARATOR;
        mkdir($tempRoot, 0775, true);

        Configure::write('Images.storageRoot', $tempRoot);
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
        ]);

        $uploadPath = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($uploadPath, $pngData);

        try {
            $images = $this->getTableLocator()->get('Images');
            $image = $images->get(1);
            $subdir = (string)$image->storage_subdir;
            $baseDir = $tempRoot . str_replace('/', DIRECTORY_SEPARATOR, $subdir) . DIRECTORY_SEPARATOR;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0775, true);
            }

            $originalData = 'ORIGINAL-BYTES';
            file_put_contents($baseDir . (string)$image->filename, $originalData);

            $image = $images->patchEntity($image, [
                'storage_subdir' => $subdir,
                'storage_path' => $subdir . '/' . (string)$image->filename,
                'hash' => hash('sha256', $originalData),
                'variants' => json_encode([
                    'thumb' => [
                        'file' => 'seed-thumb.webp',
                        'mime' => 'image/webp',
                    ],
                ]),
            ], ['validate' => false]);
            $images->saveOrFail($image);

            $processor = $this->getMockBuilder(ImageProcessor::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['process'])
                ->getMock();

            $processor->expects($this->once())->method('process')->willReturn([
                'original' => [
                    'data' => $originalData,
                    'width' => 100,
                    'height' => 50,
                    'mime' => 'image/png',
                    'ext' => 'png',
                ],
                'variants' => [
                    'thumb' => [
                        'width' => 150,
                        'height' => 150,
                        'mime' => 'image/webp',
                        'ext' => 'webp',
                    ],
                ],
            ]);

            $service = new ImageStorageService($processor, null);
            $uploadedFile = new UploadedFile(
                $uploadPath,
                filesize($uploadPath),
                UPLOAD_ERR_OK,
                'dot.png',
                'image/png',
            );

            $result = $service->upload($uploadedFile);

            $this->assertFalse($result['success'] ?? true);
            $this->assertSame('Missing data for variant thumb', (string)($result['error'] ?? ''));
            $this->assertFileDoesNotExist($baseDir . 'seed-thumb.webp');
        } finally {
            if (is_file($uploadPath)) {
                unlink($uploadPath);
            }

            $this->clearDir($tempRoot);
            if (is_dir($tempRoot)) {
                rmdir($tempRoot);
            }

            Configure::write('Images.storageRoot', $previousStorageRoot);
            Configure::write('Images.variants', $previousVariants);
        }
    }

    /**
     * Tests upload fails when writing variant bytes to nested path fails
     */
    public function testUploadFailsWhenRestoreVariantWriteFails(): void
    {
        $previousStorageRoot = Configure::read('Images.storageRoot');
        $previousVariants = Configure::read('Images.variants');

        $tempRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'racerhistory-image-storage-'
            . uniqid('', true)
            . DIRECTORY_SEPARATOR;
        mkdir($tempRoot, 0775, true);

        Configure::write('Images.storageRoot', $tempRoot);
        Configure::write('Images.variants', [
            'thumb' => ['fit' => [150, 150], 'format' => 'webp'],
        ]);

        $uploadPath = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($uploadPath, $pngData);

        try {
            $images = $this->getTableLocator()->get('Images');
            $image = $images->get(1);
            $subdir = (string)$image->storage_subdir;
            $baseDir = $tempRoot . str_replace('/', DIRECTORY_SEPARATOR, $subdir) . DIRECTORY_SEPARATOR;
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0775, true);
            }

            $originalData = 'ORIGINAL-BYTES';
            file_put_contents($baseDir . (string)$image->filename, $originalData);

            $image = $images->patchEntity($image, [
                'storage_subdir' => $subdir,
                'storage_path' => $subdir . '/' . (string)$image->filename,
                'hash' => hash('sha256', $originalData),
                'variants' => json_encode([
                    'thumb' => [
                        'file' => 'nested/path/seed-thumb.webp',
                        'mime' => 'image/webp',
                    ],
                ]),
            ], ['validate' => false]);
            $images->saveOrFail($image);

            $processor = $this->getMockBuilder(ImageProcessor::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['process'])
                ->getMock();

            $processor->expects($this->once())->method('process')->willReturn([
                'original' => [
                    'data' => $originalData,
                    'width' => 100,
                    'height' => 50,
                    'mime' => 'image/png',
                    'ext' => 'png',
                ],
                'variants' => [
                    'thumb' => [
                        'data' => 'THUMB-BYTES',
                        'width' => 150,
                        'height' => 150,
                        'mime' => 'image/webp',
                        'ext' => 'webp',
                    ],
                ],
            ]);

            $service = new ImageStorageService($processor, null);
            $uploadedFile = new UploadedFile(
                $uploadPath,
                filesize($uploadPath),
                UPLOAD_ERR_OK,
                'dot.png',
                'image/png',
            );

            $result = $service->upload($uploadedFile);

            $this->assertFalse($result['success'] ?? true);
            $this->assertSame('Failed to restore variant thumb', (string)($result['error'] ?? ''));
            $this->assertFileDoesNotExist($baseDir . 'nested/path/seed-thumb.webp');
        } finally {
            if (is_file($uploadPath)) {
                unlink($uploadPath);
            }

            $this->clearDir($tempRoot);
            if (is_dir($tempRoot)) {
                rmdir($tempRoot);
            }

            Configure::write('Images.storageRoot', $previousStorageRoot);
            Configure::write('Images.variants', $previousVariants);
        }
    }

    /**
     * Runs the clear dir routine.
     *
     * @param string $dir
     */
    private function clearDir(string $dir): void
    {
        if ($dir === '' || !is_dir($dir)) {
            return;
        }
        $items = glob(rtrim($dir, DS) . DS . '*') ?: [];
        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->clearDir($item);
                if (is_dir($item)) {
                    rmdir($item);
                }
            } elseif (is_file($item) || is_link($item)) {
                unlink($item);
            }
        }
    }
}
