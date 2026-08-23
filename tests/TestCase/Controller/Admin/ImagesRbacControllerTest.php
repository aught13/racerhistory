<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ImagesRbacControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Images',
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    /**
     * Authenticate as the seeded blogger role.
     */
    private function loginAsBlogger(): void
    {
        $this->mockIdentity([
            'id' => 3,
            'username' => 'blogger',
            'role' => 'blogger',
            'role_id' => 2,
            'email' => 'blogger@example.com',
            'status' => 'active',
            'active' => true,
        ]);
    }

    /**
     * Ensure a blogger can list images but cannot open a foreign image editor.
     */
    public function testBloggerCanReadAllImagesButCannotEditForeignImage(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/images');
        $this->assertResponseOk();
        $this->assertResponseContains('Images');

        $this->get('/admin/images/edit/1');
        $this->assertResponseCode(404);
    }

    /**
     * Ensure a blogger can edit an owned image while owner reassignment stays admin-only.
     */
    public function testBloggerCanEditOwnImage(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/images/edit/2');

        $this->assertResponseOk();
        $this->assertResponseContains('Edit Image #2');
        $this->assertResponseContains('Only administrators can reassign image ownership.');
    }
}
