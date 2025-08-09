<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\AppController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\AppController Test Case
 */
class AppControllerTest extends TestCase
{
    use IntegrationTestTrait;

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
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'role' => 'admin',
                'email' => 'admin@example.com',
                'status' => 'active'
            ]
        ]);
    }

    /**
     * Test that admin layout is set
     *
     * @return void
     */
    public function testAdminLayoutSet(): void
    {
        $request = $this->createMock(\Cake\Http\ServerRequest::class);
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
        $this->assertRedirect();
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
        $this->session([
            'Auth' => [
                'id' => 2,
                'username' => 'user',
                'role' => 'user', // Not admin
                'email' => 'user@example.com',
                'status' => 'active'
            ]
        ]);

        $this->get('/admin');
        $this->assertRedirect('/');
    }

    /**
     * Test access denied for inactive users
     *
     * @return void
     */
    public function testInactiveUserAccessDenied(): void
    {
        $this->session([
            'Auth' => [
                'id' => 3,
                'username' => 'inactive',
                'role' => 'admin',
                'email' => 'inactive@example.com',
                'status' => 'inactive' // Not active
            ]
        ]);

        $this->get('/admin');
        $this->assertRedirect();
    }
}
