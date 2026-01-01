<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRegisterDisabled(): void
    {
        // CakeDC/Users registration is disabled for the public site.
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
    }

    public function testLoginGet(): void
    {
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Login');
    }

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

    public function testLoginRedirectsToAdminWhenRedirectParamPresent(): void
    {
        $this->get('/users/login?redirect=/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('name="redirect"');
        $this->assertResponseContains('value="/admin"');
    }

    public function testRegisterGet(): void
    {
        $this->get('/users/register');
        $this->assertResponseCode(404);
    }

    public function testLogout(): void
    {
        $this->loginAsAdmin();
        $this->get('/users/logout');
        $this->assertRedirect();
    }

    public function testResetPasswordGet(): void
    {
        $this->get('/users/resetPassword');
        $this->assertResponseOk();
        $this->assertResponseContains('Enter your email address');
    }

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
