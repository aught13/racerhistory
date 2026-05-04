<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\RequestPolicy;
use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;

class RequestPolicyTest extends TestCase
{
    private RequestPolicy $policy;

    /**
     * Sets up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RequestPolicy();
    }

    /**
     * Tests can access public allows unauthenticated.
     */
    public function testCanAccessPublicAllowsUnauthenticated(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/'));
        $this->assertTrue($this->policy->canAccess(null, $request));
    }

    /**
     * Tests can access admin denies unauthenticated.
     */
    public function testCanAccessAdminDeniesUnauthenticated(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('/admin'))
            ->withParam('prefix', 'Admin');

        $this->assertFalse($this->policy->canAccess(null, $request));
    }

    /**
     * Tests can access admin allows active admin.
     */
    public function testCanAccessAdminAllowsActiveAdmin(): void
    {
        $admin = new User(['id' => 1, 'username' => 'admin', 'role' => 'admin', 'active' => true]);
        $identity = $this->createMock(IdentityInterface::class);
        $identity->method('getOriginalData')->willReturn($admin);

        $request = (new ServerRequest())
            ->withUri(new Uri('/admin'))
            ->withParam('prefix', 'Admin');

        $this->assertTrue($this->policy->canAccess($identity, $request));
    }

    /**
     * Tests can access debug kit denied.
     */
    public function testCanAccessDebugKitDenied(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/debug-kit/toolbar/abc'));
        $this->assertFalse($this->policy->canAccess(null, $request));
    }
}
