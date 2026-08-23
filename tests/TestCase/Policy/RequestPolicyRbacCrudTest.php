<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\RequestPolicy;
use Authorization\IdentityInterface;
use Cake\Cache\Cache;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;

class RequestPolicyRbacCrudTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    private RequestPolicy $policy;

    /**
     * Set up request policy fixture-backed test instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Keep this test deterministic even if RBAC cache files exist from
        // prior local runs with different permission data.
        foreach ([1, 2, 3, 4] as $roleId) {
            Cache::delete('rbac_permissions_role_' . $roleId);
        }

        $this->policy = new RequestPolicy();
    }

    /**
     * Ensure admin CRUD route access aligns with role matrix values.
     */
    public function testCrudRouteAccessMatchesRolePermissions(): void
    {
        $identity = $this->bloggerIdentity();

        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/blog-posts', 'BlogPosts', 'index')));
        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/blog-posts/add', 'BlogPosts', 'add')));
        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/blog-posts/edit/2', 'BlogPosts', 'edit')));
        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/blog-posts/delete/2', 'BlogPosts', 'delete')));

        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/images', 'Images', 'index')));
        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/images/edit/2', 'Images', 'edit')));

        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/games', 'Games', 'index')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/games/add', 'Games', 'add')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/games/edit/1', 'Games', 'edit')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/games/delete/1', 'Games', 'delete')));

        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/users/add', 'Users', 'add')));
        $this->assertTrue($this->policy->canAccess($identity, $this->adminRequest('/admin/users/edit/3', 'Users', 'edit')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/roles', 'Roles', 'index')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/roles/edit/2', 'Roles', 'edit')));
        $this->assertFalse($this->policy->canAccess($identity, $this->adminRequest('/admin/site-options/edit', 'SiteOptions', 'edit')));
    }

    /**
     * Build a request marked as an admin-prefixed route.
     *
     * @param string $path Request URI path.
     * @param string $controller Admin controller name.
     * @param string $action Admin action name.
     * @return \Cake\Http\ServerRequest
     */
    private function adminRequest(string $path, string $controller, string $action): ServerRequest
    {
        return (new ServerRequest())
            ->withUri(new Uri($path))
            ->withParam('prefix', 'Admin')
            ->withParam('controller', $controller)
            ->withParam('action', $action);
    }

    /**
     * Build an identity for the seeded blogger RBAC role.
     */
    private function bloggerIdentity(): IdentityInterface
    {
        $blogger = new User([
            'id' => 3,
            'username' => 'blogger',
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ]);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($blogger);

        return $identity;
    }
}
