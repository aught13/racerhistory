<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\RbacPermissionService;
use Cake\TestSuite\TestCase;

class RbacPermissionServiceTest extends TestCase
{
    private RbacPermissionService $service;

    /**
     * Initialize shared service instance.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RbacPermissionService();
    }

    /**
     * Ensure own-level support is limited to models with real ownership semantics.
     */
    public function testSupportsOwnLevelOnlyForWhitelistedModels(): void
    {
        $this->assertTrue($this->service->supportsOwnLevel('BlogPosts'));
        $this->assertTrue($this->service->supportsOwnLevel('Images'));
        $this->assertTrue($this->service->supportsOwnLevel('Users'));

        $this->assertFalse($this->service->supportsOwnLevel('Games'));
        $this->assertFalse($this->service->supportsOwnLevel('Roles'));
        $this->assertFalse($this->service->supportsOwnLevel('SiteOptions'));
    }

    /**
     * Ensure matrix options show No/Yes for non-own models.
     */
    public function testGetLevelOptionsForModel(): void
    {
        $this->assertSame(
            [
                RbacPermissionService::LEVEL_NONE => 'None',
                RbacPermissionService::LEVEL_OWN => 'Own',
                RbacPermissionService::LEVEL_ALL => 'All',
            ],
            $this->service->getLevelOptionsForModel('BlogPosts'),
        );

        $this->assertSame(
            [
                RbacPermissionService::LEVEL_NONE => 'No',
                RbacPermissionService::LEVEL_ALL => 'Yes',
            ],
            $this->service->getLevelOptionsForModel('Games'),
        );
    }

    /**
     * Ensure unsupported own submissions are normalized to deny.
     */
    public function testNormalizeLevelForModelRejectsUnsupportedOwn(): void
    {
        $this->assertSame('own', $this->service->normalizeLevelForModel('Users', 'own'));
        $this->assertSame('none', $this->service->normalizeLevelForModel('Games', 'own'));
        $this->assertSame('none', $this->service->normalizeLevelForModel('Games', 'invalid'));
        $this->assertSame('all', $this->service->normalizeLevelForModel('Games', 'all'));
    }
}
