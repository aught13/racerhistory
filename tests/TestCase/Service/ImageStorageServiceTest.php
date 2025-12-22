<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageStorageService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

class ImageStorageServiceTest extends TestCase
{
    /**
     * @var array<int,string>
     */
    public array $fixtures = ['app.Images'];

    private ImageStorageService $service;
    private string $storageRoot;
    private string $legacyRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storageRoot = rtrim((string)Configure::read('Images.storageRoot'), DS) . DS;
        $this->legacyRoot = rtrim((string)Configure::read('Images.legacyStorageRoot'), DS) . DS;
        $this->clearDir($this->storageRoot);
        $this->clearDir($this->legacyRoot);
        $this->service = new ImageStorageService();
    }

    protected function tearDown(): void
    {
        $this->clearDir($this->storageRoot);
        $this->clearDir($this->legacyRoot);

        parent::tearDown();
    }

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

    private function clearDir(string $dir): void
    {
        if ($dir === '' || !is_dir($dir)) {
            return;
        }
        $items = glob(rtrim($dir, DS) . DS . '*') ?: [];
        foreach ($items as $item) {
            if (is_dir($item)) {
                $this->clearDir($item);
                @rmdir($item);
            } else {
                @unlink($item);
            }
        }
    }
}
