<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @property \App\Test\Fixture\UsersFixture $Users
 * @property \App\Test\Fixture\SiteOptionsFixture $SiteOptions
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    // Fixtures removed: manual deterministic seeding in setUp()

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // Deterministic baseline seeding (fixture extension removed). Ensure IDs 1 & 2 + registration option.
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

    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
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
        $this->session(['Auth' => null]); // explicit clear
        $this->get('/admin/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Admin Login');
    }

    public function testLoginPostInvalid(): void
    {
        $this->session(['Auth' => null]);
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
        $this->post('/admin/users/approve/2');
        $this->assertRedirect('/admin/users');
    }

    public function testDelete(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/delete/2');
        $this->assertRedirect('/admin/users');
    }

    public function testBulkActivate(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'activate',
            'user_ids' => [2],
        ]);
        $this->assertRedirect('/admin/users');
    }

    public function testBulkDelete(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'delete',
            'user_ids' => [2],
        ]);
        $this->assertRedirect('/admin/users');
    }

    public function testToggleRegistration(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
    }

    public function testBulkInvalidAction(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'unknown',
            'user_ids' => [2],
        ]);
        $this->assertRedirect('/admin/users');
        $this->assertSession('Invalid bulk action.', 'Flash.flash.0.message');
    }

    public function testBulkActivateNoSelection(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'activate',
            'user_ids' => [],
        ]);
        $this->assertRedirect('/admin/users');
        $this->assertSession('No users selected.', 'Flash.flash.0.message');
    }

    public function testBulkDeleteNoSelection(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'delete',
            'user_ids' => [],
        ]);
        $this->assertRedirect('/admin/users');
        $this->assertSession('No users selected.', 'Flash.flash.0.message');
    }

    public function testToggleRegistrationDisables(): void
    {
        // Starts true in fixture, first toggle should disable
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
        $this->assertSession('Registration disabled.', 'Flash.flash.0.message');
    }

    public function testToggleRegistrationEnables(): void
    {
        // Force current value to false then toggle to enable
        $table = $this->getTableLocator()->get('SiteOptions');
        $option = $table->find()->where(['option_key' => 'registration'])->first();
        $option->value = 'false';
        $table->save($option);

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
        $this->assertSession('Registration enabled.', 'Flash.flash.0.message');
    }

    public function testToggleRegistrationCreatesWhenMissing(): void
    {
        $table = $this->getTableLocator()->get('SiteOptions');
        $table->deleteAll(['option_key' => 'registration']);

        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
        $this->assertSession('Registration disabled.', 'Flash.flash.0.message');
        $created = $table->find()->where(['option_key' => 'registration'])->first();
        $this->assertNotEmpty($created);
        $this->assertSame('false', $created->value); // logic creates disabled entry when missing
    }
}
