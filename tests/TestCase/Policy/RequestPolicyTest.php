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

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RequestPolicy();
    }

    public function testCanAccessPublicAllowsUnauthenticated(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/'));
        $this->assertTrue($this->policy->canAccess(null, $request));
    }

    public function testCanAccessAdminDeniesUnauthenticated(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('/admin'))
            ->withParam('prefix', 'Admin');

        $this->assertFalse($this->policy->canAccess(null, $request));
    }

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

    public function testCanAccessDebugKitDenied(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('/debug-kit/toolbar/abc'));
        $this->assertFalse($this->policy->canAccess(null, $request));
    }
}
