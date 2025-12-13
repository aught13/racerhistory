<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ImagesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Saved PHP error handler captured in setUp so tests can restore it in tearDown.
     * Declared to avoid dynamic property deprecation notices.
     *
     * @var callable|null
     */
    protected $savedErrorHandler = null;

    protected array $fixtures = [
        'app.Images',
    ];

    /**
     * Collect created image IDs during tests so we can cleanup files on tearDown
     *
     * @var int[]
     */
    protected array $createdImageIds = [];

    public function setUp(): void
    {
        // Capture current PHP error handler before calling parent::setUp so we
        // don't change PHPUnit's internal baseline of handlers.
        $prev = set_error_handler(function () {
        });
        restore_error_handler();
        $this->savedErrorHandler = $prev;

        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    public function testUploadSuccess(): void
    {
        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        // 1x1 png
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($tmp, $pngData);
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        if ($this->_response->getStatusCode() === 302) {
            $this->fail('Unexpected redirect to ' . $this->_response->getHeaderLine('Location'));
        }
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        // Debug output (will show in test logs)
        if (!($json['success'] ?? false)) {
            fwrite(STDERR, 'Upload debug JSON: ' . json_encode($json) . "\n");
        }
        $this->assertTrue($json['success'] ?? false, 'Upload should succeed');
        $this->assertNotEmpty($json['image']['id'] ?? null);
        $this->createdImageIds[] = (int)$json['image']['id'];
        $this->assertNotEmpty($json['image']['url'] ?? null);

    // Verify DB record has storage_path populated
        $images = $this->getTableLocator()->get('Images');
        $record = $images->get($json['image']['id']);
        $this->assertNotEmpty($record->storage_path, 'storage_path should be set');
        $this->assertStringContainsString('/', (string)$record->storage_path, 'storage_path should include subdir');
    }

    /**
     * Return the current active PHP error handler without modifying the stack.
     *
     * @return callable|array|null
     */
    protected function getCurrentErrorHandler()
    {
        $prev = set_error_handler(static function () {
        });
        restore_error_handler();

        return $prev;
    }

    public function testUploadRejectsUnsupportedMime(): void
    {
        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($tmp, 'not an image');
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'note.txt',
                'type' => 'text/plain',
                'size' => filesize($tmp),
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        if ($this->_response->getStatusCode() === 302) {
            $this->fail('Unexpected redirect to ' . $this->_response->getHeaderLine('Location'));
        }
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['success'] ?? true, 'Should fail with unsupported mime');
    }

    public function testServeOriginalAndVariant(): void
    {
        $this->mockIdentity();
        // First upload to create image with variants
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($tmp, $pngData);
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Upload should succeed');
        $id = $json['image']['id'];
        // Serve original
        $this->get('/admin/images/serve/' . $id);
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
        $originalBody = (string)$this->_response->getBody();
        $this->assertNotEmpty($originalBody, 'Original image body expected');
        // Attempt to serve thumb variant (if generated)
        $this->get('/admin/images/serve/' . $id . '?variant=thumb');
        if ($this->_response->getStatusCode() === 200) {
            $variantBody = (string)$this->_response->getBody();
            $this->assertNotEmpty($variantBody, 'Variant body expected');
        }
    }

    public function testServeMissingFileFallsBackToTransparentPng(): void
    {
        $this->mockIdentity();
        // From fixture we have id=1 but no actual file on disk.
        $this->get('/admin/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
        $body = (string)$this->_response->getBody();
        $this->assertNotEmpty($body, 'Placeholder PNG should have content');
        // Validate it looks like a PNG: starts with PNG signature bytes
        $this->assertSame("\x89PNG", substr($body, 0, 4), 'Should start with PNG signature');
    }

    public function testPublicServeRoute(): void
    {
        // No auth needed for public route
        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
    }

    public function testPublicServeVariantAfterUpload(): void
    {
        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($tmp, $pngData);
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Upload should succeed');
        $id = (int)$json['image']['id'];
        $this->createdImageIds[] = $id;
        // Public original
        $this->get('/images/serve/' . $id);
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        // Public variant (thumb)
        $this->get('/images/serve/' . $id . '?variant=thumb');
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
    }

    public function testPublicServeSupportsTransformParams(): void
    {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $this->markTestSkipped('Requires gd or imagick for image transformations');
        }

        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($tmp, $pngData);
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Upload should succeed');
        $id = (int)$json['image']['id'];
        $this->createdImageIds[] = $id;

        // Trigger the transform code path.
        $this->get('/images/serve/' . $id . '?w=1&h=1&fit=cover&fm=png&q=90');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());
    }

    public function testPublicServeMissingVariantFallsBack(): void
    {
        // record 1 exists but has no variants, request a non-existent variant
        $this->get('/images/serve/1?variant=thumb');
        $this->assertResponseOk();
        $this->assertSame('image/png', $this->_response->getHeaderLine('Content-Type'), 'Fallback should be PNG');
    }

    public function testAdminIndexRendersList(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<table', $body, 'Expected table markup in images index');
    }

    public function testAdminEditRendersForm(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/edit/1');
        $this->assertResponseOk();
        $this->assertStringContainsString('<form', (string)$this->_response->getBody(), 'Edit form should render');
    }

    public function testManipulatePostAcceptsCustomFields(): void
    {
        $this->mockIdentity();

        // Ensure the fixture image file exists on disk so manipulate() can process it.
        $dir = WWW_ROOT . 'img' . DS . 'storage' . DS . date('Y') . DS . date('m') . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir . 'seed.png';
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($path, $pngData);

        $this->post('/admin/images/manipulate/1', [
            'crop' => ['x' => 0, 'y' => 0, 'width' => 1, 'height' => 1],
            'rotate' => '10',
            'brightness' => '0',
            'contrast' => '0',
            'blur' => '0',
        ]);

        $this->assertResponseCode(302, 'Manipulate POST should redirect (not be blocked by FormProtection)');

        // Best-effort cleanup (file may be overwritten during manipulate).
        if (is_file($path)) {
            unlink($path);
        }
    }

    protected function tearDown(): void
    {
        // Remove any files created in webroot/img/storage during tests
        if (!empty($this->createdImageIds)) {
            $images = $this->getTableLocator()->get('Images');
            foreach ($this->createdImageIds as $id) {
                try {
                    $record = $images->get($id);
                } catch (\Throwable $e) {
                    continue;
                }
                $storagePath = $record->storage_path ?? null;
                if ($storagePath) {
                    $base = WWW_ROOT . 'img' . DS . 'storage' . DS;
                    // sanitize path to avoid directory traversal or backslash issues
                    $sanitized = str_replace(['..', '\\'], '', $storagePath);
                    $full = $base . ltrim($sanitized, '/\\');
                    if (is_file($full)) {
                        try {
                            unlink($full);
                        } catch (\Throwable $e) {
                            // best-effort cleanup; ignore any unlink failures
                        }
                    }
                    // try variants
                    $variants = is_string($record->variants) ? json_decode($record->variants, true) : $record->variants;
                    if (is_array($variants)) {
                        foreach ($variants as $v) {
                            if (!empty($v['file'])) {
                                $vf = dirname($full) . DS . $v['file'];
                                if (is_file($vf)) {
                                    try {
                                        unlink($vf);
                                    } catch (\Throwable $e) {
                                        // ignore cleanup failure
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Restore the saved PHP error handler to avoid leaving modified handlers
        // that cause PHPUnit to mark tests as risky. We set the saved handler and
        // then immediately restore the stack so the overall handler state matches
        // what it was at setUp time.
        if ($this->savedErrorHandler !== null) {
            $maxLoops = 20;
            $loops = 0;
            while ($loops++ < $maxLoops) {
                $curr = $this->getCurrentErrorHandler();
                if ($curr === $this->savedErrorHandler) {
                    break;
                }

                // Pop the most recent handler and try again.
                restore_error_handler();
            }

            // If after popping we still don't have the saved handler active,
            // force it to be the active handler so PHPUnit's post-test check
            // observes the original handler.
            if ($this->getCurrentErrorHandler() !== $this->savedErrorHandler) {
                set_error_handler($this->savedErrorHandler);
            }
        }

        parent::tearDown();
    }
}
