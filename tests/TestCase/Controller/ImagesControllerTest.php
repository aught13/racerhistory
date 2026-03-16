<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ImagesController;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ImagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Images',
    ];

    private string $storageRoot;

    public function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = WWW_ROOT . 'img' . DS . 'storage' . DS;
    }

    public function tearDown(): void
    {
        // Best-effort cleanup of derivative cache created by serveTransformed()
        $dir = CACHE . 'image_derivatives' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR;
        if (is_dir($dir)) {
            foreach (glob($dir . '*') ?: [] as $file) {
                if (is_file($file)) {
                    $this->safeUnlink($file);
                }
            }
            if ((glob($dir . '*') ?: []) === []) {
                $this->safeRmdir($dir);
            }
        }

        parent::tearDown();
    }

    public function testServeMissingRecordReturnsPlaceholderPng(): void
    {
        $this->get('/images/serve/999999');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('public', $this->_response->getHeaderLine('Cache-Control'));

        $body = (string)$this->_response->getBody();
        $this->assertNotEmpty($body);
        $this->assertSame("\x89PNG", substr($body, 0, 4));
    }

    public function testServeMissingFileReturnsPlaceholderPng(): void
    {
        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));

        $body = (string)$this->_response->getBody();
        $this->assertNotEmpty($body);
        $this->assertSame("\x89PNG", substr($body, 0, 4));
    }

    public function testServeWhenFileExistsReturnsBodyAndEtag(): void
    {
        $png = $this->tinyPngBytes();
        $fullPath = $this->writeFixtureImageFile(1, $png);

        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));

        $etag = $this->_response->getHeaderLine('ETag');
        $this->assertNotSame('', $etag, 'ETag expected');

        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $expected = $this->computeEtag((string)$image->get('hash'), '', []);
        $this->assertSame($expected, $etag);

        $body = (string)$this->_response->getBody();
        $this->assertSame($png, $body);

        $this->safeUnlink($fullPath);
    }

    public function testServeWithVersionEnablesLongCache(): void
    {
        $png = $this->tinyPngBytes();
        $fullPath = $this->writeFixtureImageFile(1, $png);

        $this->get('/images/serve/1?v=123');
        $this->assertResponseOk();
        $this->assertStringContainsString('immutable', $this->_response->getHeaderLine('Cache-Control'));

        $this->safeUnlink($fullPath);
    }

    public function testServeWithoutVersionUsesPublicShortCache(): void
    {
        $png = $this->tinyPngBytes();
        $fullPath = $this->writeFixtureImageFile(1, $png);

        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $cacheControl = $this->_response->getHeaderLine('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringNotContainsString('immutable', $cacheControl);

        $this->safeUnlink($fullPath);
    }

    public function testServeReturns304WhenEtagMatches(): void
    {
        $png = $this->tinyPngBytes();
        $fullPath = $this->writeFixtureImageFile(1, $png);

        // First request to get the ETag
        $this->get('/images/serve/1');
        $etag = $this->_response->getHeaderLine('ETag');
        $this->assertNotSame('', $etag);

        // Conditional request with matching ETag
        $this->configRequest(['headers' => ['If-None-Match' => $etag]]);
        $this->get('/images/serve/1');
        $this->assertResponseCode(304);

        $this->safeUnlink($fullPath);
    }

    public function testServeWithInvalidTransformParamsDoesNotTransform(): void
    {
        // fit=invalid and w=0 should yield no transform path.
        $this->get('/images/serve/1?fit=invalid&w=0&h=0&fm=bogus&q=nope');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
    }

    public function testServeTransformedUsesDerivativeCacheWhenPresent(): void
    {
        $png = $this->tinyPngBytes();
        $fullPath = $this->writeFixtureImageFile(1, $png);

        // Pre-build the cached derivative file so serveTransformed() returns from cache.
        $images = TableRegistry::getTableLocator()->get('Images');
        $image = $images->get(1);

        $variant = '';
        $transform = ['w' => 10, 'h' => 10, 'fit' => 'contain', 'fm' => 'png', 'q' => 90];
        $etag = $this->computeEtag((string)$image->get('hash'), $variant, $transform);
        $cacheDir = CACHE . 'image_derivatives' . DIRECTORY_SEPARATOR . '1' . DIRECTORY_SEPARATOR;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0775, true);
        }

        $key = hash('sha256', $etag);
        $cached = $cacheDir . $key . '.png';
        $expectedBody = 'cached-bytes';
        file_put_contents($cached, $expectedBody);

        $this->get('/images/serve/1?w=10&h=10&fit=contain&fm=png&q=90');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
        $this->assertSame($etag, $this->_response->getHeaderLine('ETag'));
        $this->assertSame($expectedBody, (string)$this->_response->getBody());

        $this->safeUnlink($cached);
        $this->safeUnlink($fullPath);
    }

    private function safeUnlink(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        set_error_handler(static fn() => true);
        unlink($path);
        restore_error_handler();
    }

    private function safeRmdir(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        set_error_handler(static fn() => true);
        rmdir($path);
        restore_error_handler();
    }

    public function testResolvePathAcceptsNumericIdViaReflection(): void
    {
        $request = new ServerRequest(['url' => '/images/serve/1']);
        $controller = $this->getMockBuilder(ImagesController::class)
            ->onlyMethods(['initialize'])
            ->setConstructorArgs([$request])
            ->getMock();

        $ref = new \ReflectionClass(ImagesController::class);
        $method = $ref->getMethod('resolvePath');
        if (PHP_VERSION_ID < 80500) {
            $method->setAccessible(true);
        }

        $result = $method->invoke($controller, 1, '');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsString($result[1]);
    }

    public function testExtractTransformParamsReturnsNullWithoutValues(): void
    {
        $controller = $this->createImagesControllerWithQuery(['variant' => 'thumb', 'v' => '1']);
        $method = $this->getPrivateMethod($controller, 'extractTransformParams');

        $this->assertNull($method->invoke($controller));
    }

    public function testExtractTransformParamsFiltersAndNormalizesInputs(): void
    {
        $controller = $this->createImagesControllerWithQuery([
            'w' => '84',
            'h' => '0',
            'fit' => 'Cover',
            'fm' => 'jpeg',
            'q' => '150',
            'variant' => 'thumb',
            'v' => '1',
        ]);
        $method = $this->getPrivateMethod($controller, 'extractTransformParams');

        $this->assertSame([
            'w' => 84,
            'fit' => 'cover',
            'fm' => 'jpg',
            'q' => 100,
        ], $method->invoke($controller));
    }

    public function testOutputFormatAndMimeHelpersCoverCommonCases(): void
    {
        $controller = $this->createImagesControllerWithQuery();
        $output = $this->getPrivateMethod($controller, 'outputFormat');
        $mimeToExt = $this->getPrivateMethod($controller, 'mimeToExt');

        $this->assertSame(['image/jpeg', 'jpg'], $output->invoke($controller, 'jpg', 'image/png'));
        $this->assertSame(['image/png', 'png'], $output->invoke($controller, 'png', 'image/jpeg'));
        $this->assertSame(['image/webp', 'webp'], $output->invoke($controller, 'webp', 'image/jpeg'));
        $this->assertSame(['image/png', 'png'], $output->invoke($controller, null, 'image/png'));
        $this->assertSame('png', $mimeToExt->invoke($controller, 'image/png'));
        $this->assertSame('webp', $mimeToExt->invoke($controller, 'image/webp'));
        $this->assertSame('jpg', $mimeToExt->invoke($controller, 'application/octet-stream'));
    }

    public function testBuildEtagProducesConsistentValue(): void
    {
        $controller = $this->createImagesControllerWithQuery();
        $method = $this->getPrivateMethod($controller, 'buildEtag');

        $expected = '"' . hash('sha256', 'hash|thumb|[]') . '"';
        $this->assertSame($expected, $method->invoke($controller, 'hash', 'thumb', []));
    }

    private function computeEtag(string $hash, string $variant, array $transform): string
    {
        $basis = $hash . '|' . $variant . '|' . json_encode($transform);

        return '"' . hash('sha256', $basis) . '"';
    }

    private function tinyPngBytes(): string
    {
        $b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=';

        return (string)base64_decode($b64);
    }

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

    private function createImagesControllerWithQuery(array $query = []): ImagesController
    {
        $request = new ServerRequest([
            'url' => '/images/serve/1',
            'query' => $query,
        ]);
        $response = new Response();

        return $this->getMockBuilder(ImagesController::class)
            ->onlyMethods(['initialize'])
            ->setConstructorArgs([$request, $response])
            ->getMock();
    }

    private function getPrivateMethod(ImagesController $controller, string $name): \ReflectionMethod
    {
        $ref = new \ReflectionClass($controller);
        $method = $ref->getMethod($name);
        if (PHP_VERSION_ID < 80500) {
            $method->setAccessible(true);
        }

        return $method;
    }
}
