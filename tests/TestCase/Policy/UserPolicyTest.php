<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\UserPolicy;
use Authorization\IdentityInterface;
use Cake\TestSuite\TestCase;

/**
 * UserPolicy Test Case
 *
 * Tests authorization policies for User resources
 */
class UserPolicyTest extends TestCase
{
    /**
     * @var \App\Policy\UserPolicy
     */
    protected UserPolicy $policy;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
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
     * Test canEdit allows user to edit their own data
     *
     * @return void
     */
    public function testCanEditOwnUser(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canEdit($identity, $resource);
        $this->assertTrue($result, 'User should be able to edit their own data');
    }

    /**
     * Test canEdit denies editing other users
     *
     * @return void
     */
    public function testCanEditOtherUserDenied(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 2, 'username' => 'otheruser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canEdit($identity, $resource);
        $this->assertFalse($result, 'User should not be able to edit other users');
    }

    /**
     * Test canEdit allows admin to edit any user
     *
     * @return void
     */
    public function testCanEditAsAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin']);
        $resource = new User(['id' => 2, 'username' => 'otheruser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);

        $result = $this->policy->canEdit($identity, $resource);
        $this->assertTrue($result, 'Admin should be able to edit any user');
    }

    /**
     * Test canDelete denies regular users
     *
     * @return void
     */
    public function testCanDeleteDeniedForRegularUser(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canDelete($identity, $resource);
        $this->assertFalse($result, 'Regular user should not be able to delete users');
    }

    /**
     * Test canDelete allows admin
     *
     * @return void
     */
    public function testCanDeleteAllowedForAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin']);
        $resource = new User(['id' => 2, 'username' => 'otheruser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);

        $result = $this->policy->canDelete($identity, $resource);
        $this->assertTrue($result, 'Admin should be able to delete users');
    }

    /**
     * Test canView allows user to view their own data
     *
     * @return void
     */
    public function testCanViewOwnUser(): void
    {
        $user = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);
        $resource = new User(['id' => 1, 'username' => 'testuser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($user);

        $result = $this->policy->canView($identity, $resource);
        $this->assertTrue($result, 'User should be able to view their own data');
    }

    /**
     * Test canView allows admin to view any user
     *
     * @return void
     */
    public function testCanViewAsAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin']);
        $resource = new User(['id' => 2, 'username' => 'otheruser', 'role' => 'user']);

        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);

        $result = $this->policy->canView($identity, $resource);
        $this->assertTrue($result, 'Admin should be able to view any user');
    }
}
