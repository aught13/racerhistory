<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\ApplicationPolicy;
use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * ApplicationPolicy Test Case
 *
 * Tests base authorization policy logic
 */
class ApplicationPolicyTest extends TestCase
{
    /**
     * @var \App\Policy\ApplicationPolicy
     */
    protected ApplicationPolicy $policy;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ApplicationPolicy();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->policy);
        parent::tearDown();
    }

    /**
     * Test canAccessAdmin returns false for unauthenticated users
     *
     * @return void
     */
    public function testCanAccessAdminUnauthenticated(): void
    {
        $request = new ServerRequest();

        $result = $this->policy->canAccessAdmin(null, $request);
        $this->assertFalse($result, 'Unauthenticated user should not access admin');
    }

    /**
     * Test canAccessAdmin returns true for admin users
     *
     * @return void
     */
    public function testCanAccessAdminAsAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin', 'active' => true]);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);
        $request = new ServerRequest();

        $result = $this->policy->canAccessAdmin($identity, $request);
        $this->assertTrue($result, 'Active admin should access admin area');
    }

    /**
     * Test canAccessAdmin returns false for non-admin users
     *
     * @return void
     */
    public function testCanAccessAdminAsNonAdmin(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user', 'active' => true]);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);
        $request = new ServerRequest();

        $result = $this->policy->canAccessAdmin($identity, $request);
        $this->assertFalse($result, 'Non-admin user should not access admin area');
    }

    /**
     * Test canAccessAdmin returns false for inactive admin
     *
     * @return void
     */
    public function testCanAccessAdminInactiveAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin', 'active' => false]);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);
        $request = new ServerRequest();

        $result = $this->policy->canAccessAdmin($identity, $request);
        $this->assertFalse($result, 'Inactive admin should not access admin area');
    }

    /**
     * Test canAccessPublic always returns true
     *
     * @return void
     */
    public function testCanAccessPublicAlwaysTrue(): void
    {
        $request = new ServerRequest();

        $result = $this->policy->canAccessPublic(null, $request);
        $this->assertTrue($result, 'Public access should always be allowed');
    }

    /**
     * Test canEditOwnProfile returns true for own profile
     *
     * @return void
     */
    public function testCanEditOwnProfile(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 1, 'username' => 'testuser']);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canEditOwnProfile($identity, $resource);
        $this->assertTrue($result, 'User should be able to edit own profile');
    }

    /**
     * Test canEditOwnProfile returns false for other user's profile
     *
     * @return void
     */
    public function testCanEditOtherProfile(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 2, 'username' => 'otheruser']);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canEditOwnProfile($identity, $resource);
        $this->assertFalse($result, 'User should not be able to edit other profiles');
    }

    /**
     * Test canEditOwnProfile returns false when unauthenticated
     *
     * @return void
     */
    public function testCanEditOwnProfileUnauthenticated(): void
    {
        $resource = new User(['id' => 1, 'username' => 'testuser']);

        $result = $this->policy->canEditOwnProfile(null, $resource);
        $this->assertFalse($result, 'Unauthenticated user should not edit profiles');
    }
}
