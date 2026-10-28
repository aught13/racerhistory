<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Component;

use App\Controller\Component\AdminDataScopeComponent;
use App\Service\AdminDataScopeService;
use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use stdClass;

class AdminDataScopeComponentTest extends TestCase
{
    /**
     * Verify the component delegates access checks to its service.
     *
     * @return void
     */
    public function testCanAccessStatAdminRequestDelegatesToService(): void
    {
        $identity = new stdClass();
        $service = $this->createMock(AdminDataScopeService::class);
        $service->expects($this->once())
            ->method('canAccessStatAdminRequest')
            ->with($identity, 'StatBasketGameBox', 'gameBox', 42)
            ->willReturn(false);

        $component = new AdminDataScopeComponent(new ComponentRegistry(), $service);

        $this->assertFalse($component->canAccessStatAdminRequest(
            $identity,
            'StatBasketGameBox',
            'gameBox',
            42,
        ));
    }
}
