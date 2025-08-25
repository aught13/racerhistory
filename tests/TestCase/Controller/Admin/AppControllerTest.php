<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\AppController;
use App\Test\TestCase\Support\AuthTestTrait;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\AppController Test Case
 */
class AppControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Set up admin session for testing
     *
     * @return void
     */
    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
    }

    /**
     * Test that admin layout is set
     *
     * @return void
     */
    public function testAdminLayoutSet(): void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new AppController($request);
        $controller->initialize();

        $layout = $controller->viewBuilder()->getLayout();
        $this->assertEquals('admin', $layout);
    }

    /**
     * Test access to admin area without authentication redirects to login
     *
     * @return void
     */
    public function testUnauthenticatedAccessRedirectsToLogin(): void
    {
        $this->get('/admin');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test access with authenticated admin user
     *
     * @return void
     */
    public function testAuthenticatedAdminAccess(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin');
        $this->assertResponseOk();
    }

    /**
     * Test access denied for non-admin users
     *
     * @return void
     */
    public function testNonAdminUserAccessDenied(): void
    {
        $this->mockIdentity([
            'id' => 2,
            'username' => 'user',
            'role' => 'user',
            'email' => 'user@example.com',
            'status' => 'active',
        ]);

        $this->get('/admin');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test access denied for inactive users
     *
     * @return void
     */
    public function testInactiveUserAccessDenied(): void
    {
        $this->mockIdentity([
            'id' => 3,
            'username' => 'inactive',
            'role' => 'admin',
            'email' => 'inactive@example.com',
            'status' => 'inactive',
        ]);

        $this->get('/admin');
        $this->assertRedirect();
    }
}
