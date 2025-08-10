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

    // Fixtures removed: manual deterministic seeding in setUp()

    public function setUp(): void
    {
        parent::setUp();
        // Re-seed baseline users & registration option to isolate from prior test side-effects.
        $users = $this->getTableLocator()->get('Users');
        $users->deleteAll([]);
        $baseline = [
            [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'username' => 'user',
                'email' => 'user@example.com',
                'password' => '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG',
                'role' => 'user',
                'status' => 'inactive',
            ],
        ];
        foreach ($baseline as $row) {
            $entity = $users->newEntity($row, ['accessibleFields' => ['*' => true]]);
            $users->saveOrFail($entity);
        }
        $siteOptions = $this->getTableLocator()->get('SiteOptions');
        $siteOptions->deleteAll(['option_key' => 'registration']);
        $option = $siteOptions->newEntity([
            'option_key' => 'registration',
            'value' => 'true',
        ], ['accessibleFields' => ['*' => true]]);
        $siteOptions->saveOrFail($option);
    }

    public function testRegisterDisabled(): void
    {
        // Simulate registration disabled in SiteOptions
        $siteOptionsTable = $this->getTableLocator()->get('SiteOptions');
        $option = $siteOptionsTable->find()->where(['option_key' => 'registration'])->first();
        if ($option) {
            $option->value = 'false';
            $siteOptionsTable->save($option);
        } else {
            $option = $siteOptionsTable->newEntity([
                'option_key' => 'registration',
                'value' => 'false',
            ]);
            $siteOptionsTable->save($option);
        }
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
        $this->assertRedirect();
    // Identity persisted in Authentication session; verify post-login redirect occurred
        $this->assertRedirectContains('/users/login');
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
        $this->assertResponseContains('Unable to register user');
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
