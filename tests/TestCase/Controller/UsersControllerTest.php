<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @link \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Use fixtures for deterministic baseline instead of manual seeding.
     *
     * @var array<string>
     */
    protected array $fixtures = ['app.Users', 'app.SiteOptions'];

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Tests register disabled.
     */
    public function testRegisterDisabled(): void
    {
        // CakeDC/Users registration is disabled for the public site.
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    /**
     * Runs the login as admin routine.
     */
    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
    }

    /**
     * Tests login get.
     */
    public function testLoginGet(): void
    {
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
    }

    /**
     * Tests login post invalid.
     */
    public function testLoginPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login', [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Username or password is incorrect');
    }

    /**
     * Tests login post valid redirects to redirect param.
     */
    public function testLoginPostValidRedirectsToRedirectParam(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login?redirect=/admin', [
            'username' => 'admin',
            'password' => 'password',
        ]);
        $this->assertRedirect('/admin');
    }

    /**
     * Tests login redirects to admin when redirect param present.
     */
    public function testLoginRedirectsToAdminWhenRedirectParamPresent(): void
    {
        $this->get('/users/login?redirect=/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('name="redirect"');
        $this->assertResponseContains('value="/admin"');
    }

    /**
     * Tests register get.
     */
    public function testRegisterGet(): void
    {
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    /**
     * Tests logout.
     */
    public function testLogout(): void
    {
        $this->loginAsAdmin();
        $this->get('/users/logout');
        $this->assertStringContainsString('/login', $this->_response->getHeaderLine('Location'));
    }

    /**
     * Tests unknown action redirects home.
     */
    public function testUnknownActionRedirectsHome(): void
    {
        $this->get('/users/doesntExist');
        $location = $this->_response->getHeaderLine('Location');
        $this->assertStringContainsString('/users/login', $location);
    }

    /**
     * Tests reset password get.
     */
    public function testResetPasswordGet(): void
    {
        $this->get('/users/resetPassword');
        $this->assertResponseOk();
        $this->assertResponseContains('Enter your email address');
    }

    /**
     * Tests reset password post valid.
     */
    public function testResetPasswordPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $data = [
            'email' => 'admin@example.com',
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('If your email exists, a reset link will be sent.');
    }

    /**
     * Tests reset password post invalid.
     */
    public function testResetPasswordPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $data = [
            'email' => '', // invalid
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('If your email exists, a reset link will be sent.');
    }

    // Add more tests for other actions as needed
}
