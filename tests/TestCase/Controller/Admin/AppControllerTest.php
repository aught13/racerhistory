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
            'email' => 'user@example.com',
            'status' => 'active',
        ]);
        $this->get('/admin');
        $this->assertFlashMessage('You do not have permission to access the admin area.');

        // Test inactive user
        $this->mockIdentity([
            'id' => 3,
            'username' => 'inactive',
            'role' => 'admin',
            'email' => 'inactive@example.com',
            'status' => 'inactive',
        ]);
        $this->get('/admin');
        $this->assertFlashMessage('Your account is not active. Please contact an administrator.');
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
     * @return void
     */
    public function testExtractUserFieldWithDifferentDataTypes(): void
    {
        $request = $this->createMock(ServerRequest::class);
        $controller = new AppController($request);

        // Use reflection to access private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractUserField');
        $method->setAccessible(true);

        // Test with array
        $arrayData = ['status' => 'active', 'role' => 'admin'];
        $this->assertEquals('active', $method->invoke($controller, $arrayData, 'status'));
        $this->assertEquals('admin', $method->invoke($controller, $arrayData, 'role'));
        $this->assertNull($method->invoke($controller, $arrayData, 'nonexistent'));

        // Test with ArrayAccess object
        $arrayAccessData = new \ArrayObject(['status' => 'inactive', 'role' => 'user']);
        $this->assertEquals('inactive', $method->invoke($controller, $arrayAccessData, 'status'));
        $this->assertEquals('user', $method->invoke($controller, $arrayAccessData, 'role'));

        // Test with object having get() method
        $mockEntity = $this->createMock(\Cake\ORM\Entity::class);
        $mockEntity->method('get')->willReturnMap([
            ['status', 'pending'],
            ['role', 'editor'],
        ]);
        $this->assertEquals('pending', $method->invoke($controller, $mockEntity, 'status'));
        $this->assertEquals('editor', $method->invoke($controller, $mockEntity, 'role'));

        // Test with object having properties
        $objectData = new \stdClass();
        $objectData->status = 'disabled';
        $objectData->role = 'moderator';
        $this->assertEquals('disabled', $method->invoke($controller, $objectData, 'status'));
        $this->assertEquals('moderator', $method->invoke($controller, $objectData, 'role'));

        // Test with object having accessor methods
        $mockUser = new class {
            public function getStatus(): string
            {
                return 'verified';
            }

            public function getRole(): string
            {
                return 'admin';
            }
        };
        $this->assertEquals('verified', $method->invoke($controller, $mockUser, 'status'));
        $this->assertEquals('admin', $method->invoke($controller, $mockUser, 'role'));

        // Test with null/invalid data
        $this->assertNull($method->invoke($controller, null, 'status'));
        $this->assertNull($method->invoke($controller, 'string', 'status'));
        $this->assertNull($method->invoke($controller, 123, 'status'));
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
