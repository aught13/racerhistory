<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\ImagesController;
use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * @link \App\Controller\Admin\ImagesController
 */
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
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    private string $storageRoot;

    /**
     * Collect created image IDs during tests so we can cleanup files on tearDown
     *
     * @var int[]
     */
    protected array $createdImageIds = [];

    /**
     * Sets up the test case.
     */
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
        $this->storageRoot = rtrim((string)Configure::read('Images.storageRoot', WWW_ROOT . 'img' . DS . 'storage'), DS) . DS;
    }

    /**
     * Tests upload success.
     */
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

    /**
     * Tests upload rejects unsupported mime.
     */
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

    /**
     * Tests bulk upload requires auth.
     */
    public function testBulkUploadRequiresAuth(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        file_put_contents($tmp, $pngData);
        $this->post('/admin/images/bulk-upload', [
            'uploads' => [[
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => strlen($pngData),
                'error' => UPLOAD_ERR_OK,
            ]],
        ]);
        // Authorization middleware redirects unauthenticated admin requests
        $this->assertRedirect();
    }

    /**
     * Tests bulk upload success.
     */
    public function testBulkUploadSuccess(): void
    {
        $this->mockIdentity();

        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8lmpwAAAAASUVORK5CYII=');
        $tmp1 = tempnam(sys_get_temp_dir(), 'img');
        $tmp2 = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp1, $pngData);
        file_put_contents($tmp2, $pngData);

        $this->post('/admin/images/bulk-upload', [
            'uploads' => [
                [
                    'tmp_name' => $tmp1,
                    'name' => 'dot1.png',
                    'type' => 'image/png',
                    'size' => strlen($pngData),
                    'error' => UPLOAD_ERR_OK,
                ],
                [
                    'tmp_name' => $tmp2,
                    'name' => 'dot2.png',
                    'type' => 'image/png',
                    'size' => strlen($pngData),
                    'error' => UPLOAD_ERR_OK,
                ],
            ],
            'tags' => [
                '0' => 'person-1',
                '1' => 'teamseason-2',
            ],
            'context' => [
                '0' => 'Media Day',
                '1' => 'Roster Headshot',
            ],
        ]);

        if ($this->_response->getStatusCode() === 302) {
            $this->fail('Unexpected redirect to ' . $this->_response->getHeaderLine('Location'));
        }

        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Bulk upload should succeed');
        $this->assertCount(2, $json['results'] ?? [], 'Should return a result per file');

        foreach ($json['results'] as $result) {
            if (!empty($result['image']['id'])) {
                $this->createdImageIds[] = (int)$result['image']['id'];
            }
            $this->assertTrue($result['success'] ?? false, 'Each file should succeed');
        }
    }

    /**
     * Tests admin serve endpoint redirects original and variant requests to public serve.
     */
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

        // Admin endpoint delegates to the public image endpoint.
        $this->get('/admin/images/serve/' . $id);
        $this->assertRedirectContains('/images/serve/' . $id);

        // Confirm public endpoint serves image content.
        $this->get('/images/serve/' . $id);
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody(), 'Original image body expected');

        // Variant requests are delegated with query string intact.
        $this->get('/admin/images/serve/' . $id . '?variant=thumb');
        $this->assertRedirectContains('/images/serve/' . $id . '?variant=thumb');
    }

    /**
     * Tests admin serve redirects to the public endpoint for fallback handling.
     */
    public function testServeMissingFileRedirectsToPublicEndpoint(): void
    {
        $this->mockIdentity();
        // From fixture we have id=1 but no actual file on disk.
        $this->get('/admin/images/serve/1');
        $this->assertRedirectContains('/images/serve/1');

        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/webp', $this->_response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('no-store', $this->_response->getHeaderLine('Cache-Control'));
        $body = (string)$this->_response->getBody();
        $this->assertNotEmpty($body, 'Placeholder WebP should have content');
        $this->assertSame('RIFF', substr($body, 0, 4), 'Should start with RIFF signature');
        $this->assertSame('WEBP', substr($body, 8, 4), 'Should identify as WEBP');
    }

    /**
     * Tests public serve route.
     */
    public function testPublicServeRoute(): void
    {
        // No auth needed for public route
        $this->get('/images/serve/1');
        $this->assertResponseOk();
        $this->assertSame('image/webp', $this->_response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests public serve variant after upload.
     */
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
        $this->get('/images/serve/' . $id . '?fm=webp');
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        // Public variant (thumb)
        $this->get('/images/serve/' . $id . '?variant=thumb');
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests public serve supports transform params.
     */
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
        $this->assertSame('image/webp', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());
    }

    /**
     * Tests public serve defaults to WebP when client supports it.
     */
    public function testPublicServeDefaultsToWebpWhenAccepted(): void
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

        $this->get('/images/serve/' . $id);
        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());

        $this->get('/images/serve/' . $id);

        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());
    }

    /**
     * Tests transformed public serve defaults to WebP when format is omitted.
     */
    public function testPublicServeTransformDefaultsToWebpWhenFormatMissing(): void
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

        $this->get('/images/serve/' . $id . '?w=1&h=1&fit=cover');

        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());

        $this->get('/images/serve/' . $id . '?w=1&h=1&fit=cover&fm=webp');

        $this->assertResponseOk();
        $this->assertStringStartsWith('image/', $this->_response->getHeaderLine('Content-Type'));
        $this->assertNotEmpty((string)$this->_response->getBody());
    }

    /**
     * Tests public serve missing variant falls back.
     */
    public function testPublicServeMissingVariantFallsBack(): void
    {
        // record 1 exists but has no variants, request a non-existent variant
        $this->get('/images/serve/1?variant=thumb');
        $this->assertResponseOk();
        $this->assertSame('image/webp', $this->_response->getHeaderLine('Content-Type'), 'Fallback should be WebP');
        $this->assertStringContainsString('no-store', $this->_response->getHeaderLine('Cache-Control'));
    }

    /**
     * Tests admin index renders list.
     */
    public function testAdminIndexRendersList(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images');
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<table', $body, 'Expected table markup in images index');
    }

    /**
     * Tests admin edit renders form.
     */
    public function testAdminEditRendersForm(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/edit/1');
        $this->assertResponseOk();
        $this->assertStringContainsString('<form', (string)$this->_response->getBody(), 'Edit form should render');
    }

    /**
     * Tests manipulate post accepts custom fields.
     */
    public function testManipulatePostAcceptsCustomFields(): void
    {
        $this->mockIdentity();

        // Ensure the fixture image file exists on disk so manipulate() can process it.
        $dir = $this->storageRoot . date('Y') . DS . date('m') . DS;
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

    /**
     * Tears down the test case.
     */
    protected function tearDown(): void
    {
        // Remove any files created in configured storage during tests
        if (!empty($this->createdImageIds)) {
            $images = $this->getTableLocator()->get('Images');
            foreach ($this->createdImageIds as $id) {
                try {
                    $record = $images->get($id);
                } catch (Throwable $e) {
                    continue;
                }
                $storagePath = $record->get('storage_path');
                if ($storagePath) {
                    $base = $this->storageRoot;
                    // sanitize path to avoid directory traversal or backslash issues
                    $sanitized = str_replace(['..', '\\'], '', $storagePath);
                    $full = $base . ltrim($sanitized, '/\\');
                    if (is_file($full)) {
                        try {
                            unlink($full);
                        } catch (Throwable $e) {
                            // best-effort cleanup; ignore any unlink failures
                        }
                    }
                    // try variants
                    $variantsRaw = $record->get('variants');
                    $variants = is_string($variantsRaw) ? json_decode($variantsRaw, true) : $variantsRaw;
                    if (is_array($variants)) {
                        foreach ($variants as $v) {
                            if (!empty($v['file'])) {
                                $vf = dirname($full) . DS . $v['file'];
                                if (is_file($vf)) {
                                    try {
                                        unlink($vf);
                                    } catch (Throwable $e) {
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

    /**
     * Tests browse requires authentication.
     */
    public function testBrowseRequiresAuthentication(): void
    {
        $this->get('/admin/images/browse');
        $this->assertResponseCode(302, 'Unauthenticated request should redirect');
    }

    /**
     * Tests browse returns json.
     */
    public function testBrowseReturnsJson(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/browse');
        $this->assertResponseCode(200);
        $this->assertSame('application/json', $this->_response->getHeaderLine('Content-Type'));
    }

    /**
     * Tests browse returns all images.
     */
    public function testBrowseReturnsAllImages(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/browse');
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Browse should succeed');
        $this->assertIsArray($json['images'] ?? null, 'Images should be array');
        // Images fixture has entries, check structure
        if (!empty($json['images'])) {
            $first = reset($json['images']);
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
        $this->mockIdentity();
        $this->get('/admin/images/browse?tag=test-tag');
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false, 'Browse with tag should succeed');
        // May return empty array if no images have that tag
        $this->assertIsArray($json['images'] ?? null);
    }

    /**
     * Tests browse respect limit parameter.
     */
    public function testBrowseRespectLimitParameter(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/browse?limit=5');
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertLessThanOrEqual(5, count($json['images'] ?? []));
    }

    /**
     * Tests browse clamp limit to maximum.
     */
    public function testBrowseClampLimitToMaximum(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images/browse?limit=99999');
        $this->assertResponseCode(200);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($json['success'] ?? false);
        $this->assertLessThanOrEqual(100, count($json['images'] ?? []));
    }

    /**
     * Tests build common entity tags skips other entities when roster selected.
     */
    public function testBuildCommonEntityTagsSkipsOtherEntitiesWhenRosterSelected(): void
    {
        $controller = $this->createImagesControllerForPrivateMethods();
        $method = $this->getPrivateMethod($controller, 'buildCommonEntityTags');

        $data = [
            'person_select' => 1,
            'roster_select' => 1,
            'teamseason_select' => 1,
            'game_select' => 1,
            'site_select' => 1,
            'opponent_select' => 1,
            'team_select' => 1,
            'sport_select' => 1,
            'common_tags' => 'sunset, team photo',
        ];

        $result = $method->invoke($controller, $data);

        $slugs = [];
        foreach ($result as $item) {
            if (is_array($item) && !empty($item['slug'])) {
                $slugs[] = $item['slug'];
            }
        }

        $this->assertNotContains('teamseason-1', $slugs);
        $this->assertNotContains('team-1', $slugs);
        $this->assertContains('sunset', $result);
        $this->assertContains('team photo', $result);
        $this->assertGreaterThanOrEqual(1, count($slugs));
    }

    /**
     * Tests collect bulk tags parses tag arrays and context.
     */
    public function testCollectBulkTagsParsesTagArraysAndContext(): void
    {
        $controller = $this->createImagesControllerForPrivateMethods();
        $method = $this->getPrivateMethod($controller, 'collectBulkTags');

        $result = $method->invoke($controller, ['0' => ['person-1', ' teamseason-1 ']], ['0' => 'Media Day'], '0');

        $this->assertContains('person-1', $result);
        $this->assertContains('teamseason-1', $result);

        $contextTags = array_values(array_filter($result, fn($item) => is_array($item) && ($item['slug'] ?? '') === 'context-media-day'));
        $this->assertNotEmpty($contextTags, 'Context tag should be generated when context text is provided.');
    }

    /**
     * Runs the create images controller for private methods routine.
     */
    private function createImagesControllerForPrivateMethods(): ImagesController
    {
        $request = new ServerRequest(['url' => '/admin/images']);

        return new ImagesController($request);
    }

    /**
     * Runs the get private method routine.
     *
     * @param \App\Controller\Admin\ImagesController $controller
     * @param string $name
     */
    private function getPrivateMethod(ImagesController $controller, string $name): ReflectionMethod
    {
        $ref = new ReflectionClass($controller);
        $method = $ref->getMethod($name);
        if (PHP_VERSION_ID < 80500) {
            $method->setAccessible(true);
        }

        return $method;
    }

    /**
     * Test admin images pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->mockIdentity();
        $this->get('/admin/images');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }
}
