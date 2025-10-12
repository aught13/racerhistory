<?php
// tests/TestCase/Controller/Admin/ImagesControllerEdgeTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ImagesControllerEdgeTest extends TestCase
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
        'app.Users',
    ];

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

    public function testUploadZeroByteFile(): void
    {
        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, '');
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'empty.png',
                'type' => 'image/png',
                'size' => 0,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['success'] ?? true);
    }

    public function testUploadMalformedImage(): void
    {
        $this->mockIdentity();
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, 'notanimage');
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'bad.png',
                'type' => 'image/png',
                'size' => 10,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['success'] ?? true);
    }

    public function testUploadUnauthorized(): void
    {
        // No identity
        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, 'x');
    // Ensure legacy Auth session fully cleared.
        $this->session(['Auth' => ['id' => null]]);
        $this->post('/admin/images/upload', [
            'upload' => [
                'tmp_name' => $tmp,
                'name' => 'dot.png',
                'type' => 'image/png',
                'size' => 1,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $json = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($json['success'] ?? true);
        $this->assertStringContainsString('Unauthenticated', $json['error'] ?? '');
    }

    public function testDeleteNonexistentImage(): void
    {
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/images/delete/9999');
        $this->assertResponseCode(404);
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

    protected function tearDown(): void
    {
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

            if ($this->getCurrentErrorHandler() !== $this->savedErrorHandler) {
                set_error_handler($this->savedErrorHandler);
            }
        }

        parent::tearDown();
    }
}
