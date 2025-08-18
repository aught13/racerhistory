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
        // Explicitly toggle registration to disabled and assert flash
        $table = $this->getTableLocator()->get('SiteOptions');
        $opt = $table->find()->where(['option_key' => 'registration'])->first();
        $opt->value = 'false';
        $table->save($opt);
        $this->get('/users/register');
        $this->assertResponseOk();
        $this->assertResponseContains('Registration is currently disabled.');
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
        $this->assertResponseContains('Invalid username or password');
    }

    public function testLoginRedirectsToAdminWhenRedirectParamPresent(): void
    {
        // Inject authenticated session (bypass credential flow for deterministic redirect test)
        $this->mockIdentity();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/users/login?redirect=/admin', [
            'redirect' => '/admin',
        ]);
        $this->assertRedirect('/admin');
    }

    public function testRegisterGet(): void
    {
        $this->get('/users/register');
        $this->assertResponseOk();
        $this->assertResponseContains('Register');
    }

    public function testRegisterPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $data = [
            'username' => 'newuser',
            'password' => 'newpassword',
            'email' => 'newuser@example.com',
            'role' => 'user',
            'status' => 'active',
        ];
        $this->post('/users/register', $data);
        // Controller may render form again or redirect; assert user persisted
        $user = $this->getTableLocator()->get('Users')->find()->where(['username' => 'newuser'])->first();
        $this->assertNotEmpty($user, 'User should have been created');
    }

    public function testRegisterPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $data = [
            'username' => '', // invalid
            'password' => '', // invalid
            'email' => '', // invalid
            'role' => '', // invalid
            'status' => 'active',
        ];
        $this->post('/users/register', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('Register');
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
        $this->assertResponseContains('Reset Password');
    }

    public function testResetPasswordPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $data = [
            'email' => 'admin@example.com',
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('If your email exists, a reset link will be sent.');
    }

    public function testResetPasswordPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $data = [
            'email' => '', // invalid
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('If your email exists, a reset link will be sent.');
    }

    // Add more tests for other actions as needed
}