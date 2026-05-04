<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @property \App\Test\Fixture\UsersFixture $Users
 * @property \App\Test\Fixture\SiteOptionsFixture $SiteOptions
 * @link \App\Controller\Admin\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    /**
     * Deterministic baseline via fixtures.
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
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * Runs the login as admin routine.
     */
    private function loginAsAdmin(): void
    {
        $this->mockIdentity();
    }

    /**
     * Tests index.
     */
    public function testIndex(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users');
        $this->assertResponseOk();
        $this->assertResponseContains('Manage Users');
    }

    /**
     * Tests login get.
     */
    public function testLoginGet(): void
    {
        $this->session(['Auth' => null]); // explicit clear
        $this->get('/admin/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('Admin Login');
    }

    /**
     * Tests login post invalid.
     */
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

    /**
     * Tests add get.
     */
    public function testAddGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Add User');
    }

    /**
     * Tests edit get.
     */
    public function testEditGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/edit/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit User');
    }

    /**
     * Tests manage get.
     */
    public function testManageGet(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users/manage/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Manage User');
    }

    /**
     * Tests approve.
     */
    public function testApprove(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // Ensure user 2 exists (fixture baseline)
        $this->assertNotNull($this->getTableLocator()->get('Users')->get(2));
        $this->post('/admin/users/approve/2');
        $this->assertRedirect();
    }

    /**
     * Tests delete.
     */
    public function testDelete(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->assertNotNull($this->getTableLocator()->get('Users')->get(2));
        $this->post('/admin/users/delete/2');
        $this->assertRedirect();
    }

    /**
     * Tests bulk activate.
     */
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

    /**
     * Tests bulk delete.
     */
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

    /**
     * Bulk delete with mixed invalid ids should only delete valid numeric.
     */
    public function testBulkDeleteSanitizesIds(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // user 2 exists; others invalid -> expect exactly 1 deletion
        $this->post('/admin/users/bulk', [
            'bulk_action' => 'delete',
            'user_ids' => ['', 'xyz', '2', 'notanumber'],
        ]);
        $this->assertRedirect('/admin/users');
        $this->assertSession('1 user(s) have been deleted.', 'Flash.flash.0.message');
    }

    /**
     * Tests toggle registration.
     */
    public function testToggleRegistration(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
    }

    /**
     * Tests bulk invalid action.
     */
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

    /**
     * Tests bulk activate no selection.
     */
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

    /**
     * Tests bulk delete no selection.
     */
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

    /**
     * Users index contains confirm delete modal element.
     */
    public function testIndexContainsConfirmDeleteModal(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users');
        $this->assertResponseOk();
        $this->assertResponseContains('id="confirm-delete-modal"');
    }

    /**
     * Tests toggle registration disables.
     */
    public function testToggleRegistrationDisables(): void
    {
        // Starts true in fixture (value 'true'), first toggle should disable
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/admin/users/toggle-registration');
        $this->assertRedirect('/admin/users');
        $this->assertSession('Registration disabled.', 'Flash.flash.0.message');
    }

    /**
     * Tests toggle registration enables.
     */
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

    /**
     * Tests toggle registration creates when missing.
     */
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

    /**
     * Test admin users pages include turbo-frame for SPA navigation.
     */
    public function testAdminPagesContainTurboFrame(): void
    {
        $this->loginAsAdmin();
        $this->get('/admin/users');
        $this->assertResponseOk();
        $this->assertResponseContains('<turbo-frame id="admin-content"');
    }
}
