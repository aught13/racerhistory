<?php
namespace App\Test\TestCase\Controller;

use App\Controller\UsersController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
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
                'value' => 'false'
            ]);
            $siteOptionsTable->save($option);
        }
        $this->get('/users/register');
        $this->assertResponseOk();
        $this->assertResponseContains('Registration is currently disabled.');
    }
    use IntegrationTestTrait;

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
            'status' => 'active'
        ];
        $this->post('/users/register', $data);
        $this->assertRedirect();
        $this->assertSession('newuser', 'Auth.username');
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
            'status' => 'active'
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
            'email' => 'admin@example.com'
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
            'email' => '' // invalid
        ];
        $this->post('/users/resetPassword', $data);
        $this->assertResponseOk();
        $this->assertResponseContains('If your email exists, a reset link will be sent.');
    }

    // Add more tests for other actions as needed
}
