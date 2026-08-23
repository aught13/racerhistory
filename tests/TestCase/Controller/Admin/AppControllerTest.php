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
 *
 * @link \App\Controller\Admin\AppController
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
        'app.Roles',
        'app.Permissions',
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
     * Test access allowed for non-admin users with RBAC admin-area permissions
     *
     * @return void
     */
    public function testRbacEditorCanAccessAdmin(): void
    {
        $this->mockIdentity([
            'id' => 4,
            'username' => 'editor',
            'role' => 'editor',
            'role_id' => 3,
            'email' => 'editor@example.com',
            'status' => 'active',
            'active' => true,
        ]);

        $this->get('/admin');
        $this->assertResponseOk();
    }

    /**
     * Test access denied for users without any admin permissions
     *
     * @return void
     */
    public function testUserWithoutAdminPermissionsAccessDenied(): void
    {
        $this->mockIdentity([
            'id' => 2,
            'username' => 'user',
            'role' => 'user',
            'role_id' => null,
            'email' => 'user@example.com',
            'status' => 'active',
            'active' => true,
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

    /**
     * Test login action bypasses authentication checks
     *
     * @return void
     */
    public function testLoginActionBypassesAuthChecks(): void
    {
        // Should not redirect when accessing login action without authentication
        $this->get('/admin/users/login');
        $this->assertResponseOk();
    }

    /**
     * Test flash messages are set for different rejection scenarios
     *
     * @return void
     */
    public function testFlashMessagesForAccessDenied(): void
    {
        $this->enableRetainFlashMessages();

        // Test unauthenticated access
        $this->get('/admin');
        $this->assertFlashMessage('You must be logged in to access the admin area.');

        // Test non-admin role
        $this->mockIdentity([
            'id' => 2,
            'username' => 'user',
            'role' => 'user',
            'role_id' => null,
            'email' => 'user@example.com',
            'status' => 'active',
            'active' => true,
        ]);
        $this->get('/admin');
        $this->assertFlashMessage('You do not have permission to access the admin area.');

        // Test inactive admin user (has admin role but status is not active)
        $this->mockIdentity([
            'id' => 3,
            'username' => 'inactive',
            'role' => 'admin',
            'email' => 'inactive@example.com',
            'status' => 'inactive',
        ]);
        $this->get('/admin');
        $this->assertFlashMessage('You do not have permission to access the admin area.');
    }

    /**
     * Test redirect URL is preserved for unauthenticated users
     *
     * @return void
     */
    public function testRedirectUrlPreserved(): void
    {
        $this->get('/admin/seasons/view/1');
        $this->assertRedirectContains('redirect=%2Fadmin%2Fseasons%2Fview%2F1');
    }

    /**
     * Test extractUserField method with different data types
     *
     * This test is obsolete - the extractUserField method was removed when
     * refactoring to use Authorization policies instead of custom role checking.
     * The authorization logic now resides in RequestPolicy::canAccessAdmin().
     *
     * @return void
     */
    public function testExtractUserFieldWithDifferentDataTypes(): void
    {
        $this->markTestSkipped('extractUserField method removed in favor of Authorization policies');
    }

    /**
     * Test user missing status field is allowed (for test fixtures)
     *
     * @return void
     */
    public function testUserWithoutStatusFieldAllowed(): void
    {
        $this->mockIdentity([
            'id' => 1,
            'username' => 'admin',
            'role' => 'admin',
            'email' => 'admin@example.com',
            // Note: no 'status' field
        ]);

        $this->get('/admin');
        $this->assertResponseOk();
    }

    /**
     * Test authentication component fallback logic
     *
     * @return void
     */
    public function testAuthenticationComponentFallback(): void
    {
        // This test ensures the legacy Auth session handling works
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'role' => 'admin',
                'email' => 'admin@example.com',
                'status' => 'active',
            ],
        ]);

        $this->get('/admin');
        $this->assertResponseOk();
    }
}
