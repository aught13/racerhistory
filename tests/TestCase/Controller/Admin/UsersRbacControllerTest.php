<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersRbacControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.SiteOptions',
    ];

    /**
     * Authenticate as the seeded blogger role.
     */
    private function loginAsBlogger(): void
    {
        $this->mockIdentity([
            'id' => 3,
            'username' => 'blogger',
            'role' => 'blogger',
            'role_id' => 2,
            'email' => 'blogger@example.com',
            'status' => 'active',
            'active' => true,
        ]);
    }

    /**
     * Bloggers should only see their own account in the users index when Users read=own.
     */
    public function testBloggerIndexShowsOnlyOwnAccount(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/users');

        $this->assertResponseOk();
        $this->assertResponseContains('blogger');
        $this->assertResponseNotContains('admin@example.com');
    }

    /**
     * Bloggers should be able to edit only their own user row.
     */
    public function testBloggerCanEditOwnUserButNotAnotherUser(): void
    {
        $this->loginAsBlogger();

        $this->get('/admin/users/edit/3');
        $this->assertResponseOk();
        $this->assertResponseContains('Edit User');

        $this->get('/admin/users/edit/1');
        $this->assertResponseCode(404);
    }

    /**
     * Bloggers should be able to update their own profile when Users update=own.
     */
    public function testBloggerCanSaveOwnProfileChanges(): void
    {
        $this->loginAsBlogger();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/admin/users/edit/3', [
            'display_name' => 'Blogger Updated Name',
        ]);

        $this->assertRedirect('/admin/users');
        $this->assertSession('User has been updated successfully.', 'Flash.flash.0.message');

        $updated = $this->getTableLocator()->get('Users')->get(3);
        $this->assertSame('Blogger Updated Name', $updated->display_name);
    }

    /**
     * Bloggers should not be able to create users when Users create=false.
     */
    public function testBloggerCannotOpenAddUserPage(): void
    {
        $this->loginAsBlogger();
        $this->get('/admin/users/add');

        $this->assertRedirectContains('/users/login');
    }
}
