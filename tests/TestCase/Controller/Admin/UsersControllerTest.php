<?php
namespace App\Test\TestCase\Controller\Admin;

use App\Controller\Admin\UsersController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @property \App\Test\Fixture\UsersFixture $Users
 * @property \App\Test\Fixture\SiteOptionsFixture $SiteOptions
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    protected array $fixtures = [
        'app.Users',
        'app.SiteOptions',
    ];

    public function setUp(): void
    {
        parent::setUp();
    }

    private function loginAsAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'role' => 'admin',
                'email' => 'admin@example.com',
                'status' => 'active'
            ],
        ]);
    }

    public function testIndex(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users');
        $this->assertResponseOk();
        $this->assertResponseContains('Manage Users');
    }

    public function testLoginGet(): void
    {
        $this->get('/admin/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Admin Login');
    }

    public function testLoginPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/login', [
            'username' => 'wrong',
            'password' => 'wrong',
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Invalid username or password');
    }

    public function testAddGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add User');
    }

    public function testEditGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit User');
    }

    public function testManageGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/manage/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Manage User');
    }

    public function testApprove(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/approve/1');
        $this->assertRedirect();
    }

    public function testDelete(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/delete/1');
        $this->assertRedirect();
    }

    public function testBulkActivate(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulkActivate');
        $this->assertRedirect();
    }

    public function testBulkDelete(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulkDelete');
        $this->assertRedirect();
    }

    public function testToggleRegistration(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggleRegistration');
        $this->assertRedirect();
    }

    // Add more tests for add, edit, delete, bulk actions, etc.
}