<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\RbacPermissionService;
use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class RbacPermissionServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    private RbacPermissionService $service;

    /**
     * Initialize shared service instance.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([1, 2, 3, 4] as $roleId) {
            Cache::delete('rbac_permissions_role_' . $roleId);
        }
        foreach (['admin', 'blogger', 'editor', 'contributor'] as $roleName) {
            Cache::delete('rbac_role_id_' . $roleName);
        }

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

    /**
     * Route-level admin access should follow action/controller mappings.
     */
    public function testCanAccessAdminRequestAndHasAnyAdminAccess(): void
    {
        $inactive = [
            'id' => 2,
            'role' => 'user',
            'role_id' => 4,
            'status' => 'inactive',
            'active' => false,
        ];
        $contributor = [
            'id' => 5,
            'role' => 'contributor',
            'role_id' => 4,
            'status' => 'active',
            'active' => true,
        ];

        $this->assertFalse($this->service->hasAnyAdminAccess($inactive));
        $this->assertTrue($this->service->hasAnyAdminAccess($contributor));

        $this->assertFalse($this->service->canAccessAdminRequest($contributor, 'Games', 'index', '/debug-kit/panel'));
        $this->assertTrue($this->service->canAccessAdminRequest($contributor, 'Dashboard', 'index'));
        $this->assertTrue($this->service->canAccessAdminRequest($contributor, 'Games', 'index'));
        $this->assertFalse($this->service->canAccessAdminRequest($contributor, 'Games', 'delete'));
        $this->assertFalse($this->service->canAccessAdminRequest($contributor, 'Unknown', 'index'));
        $this->assertFalse($this->service->canAccessAdminRequest($contributor, 'Games', 'unknownAction'));
    }

    /**
     * Permission checks should honor create/own/none semantics and ownership.
     */
    public function testCanAndScopeQueryHonorPermissionLevels(): void
    {
        $blogger = [
            'id' => 3,
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ];

        $this->assertTrue($this->service->can($blogger, 'BlogPosts', 'create'));
        $this->assertTrue($this->service->can($blogger, 'Images', 'update', ['user_id' => 3]));
        $this->assertFalse($this->service->can($blogger, 'Images', 'update', ['user_id' => 1]));
        $this->assertFalse($this->service->can($blogger, 'Images', 'delete'));

        $usersScoped = $this->service->scopeQuery(
            $blogger,
            'Users',
            TableRegistry::getTableLocator()->get('Users')->find(),
            'read',
            'id',
        )
            ->select(['Users.id'])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        $this->assertSame([3], array_values(array_map('intval', $usersScoped)));

        $rolesScoped = $this->service->scopeQuery(
            $blogger,
            'Roles',
            TableRegistry::getTableLocator()->get('Roles')->find(),
            'read',
            'id',
        );
        $this->assertSame(0, $rolesScoped->count());
    }

    /**
     * Custom rules and role resolution should use fixture-backed RBAC rows.
     */
    public function testCustomRulesAndRoleResolution(): void
    {
        $editor = [
            'id' => 4,
            'role' => 'editor',
            'role_id' => 3,
            'status' => 'active',
            'active' => true,
        ];
        $blogger = [
            'id' => 3,
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ];

        $this->assertTrue($this->service->allowsCustomRule($editor, 'BlogPosts', RbacPermissionService::BLOG_RULE_CAN_PIN));
        $this->assertFalse($this->service->allowsCustomRule($blogger, 'BlogPosts', RbacPermissionService::BLOG_RULE_CAN_PIN));

        $this->assertSame(2, $this->service->getRoleIdForIdentity(['role' => 'author']));
        $this->assertSame('editor', $this->service->getRoleNameForIdentity(['role_id' => 3]));
        $this->assertSame('blogger', $this->service->getRoleNameForIdentity(['role' => 'Author']));

        $defaults = $this->service->buildDefaultPermissionPayload('BlogPosts');
        $this->assertFalse($defaults['can_create']);
        $this->assertArrayHasKey(RbacPermissionService::BLOG_RULE_CAN_PIN, $defaults['custom_rules']);
        $this->assertFalse((bool)$defaults['custom_rules'][RbacPermissionService::BLOG_RULE_CAN_PIN]);
    }
}
