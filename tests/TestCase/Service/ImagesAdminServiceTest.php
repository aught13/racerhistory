<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageDeleteService;
use App\Service\ImageEditService;
use App\Service\ImagesAdminService;
use App\Service\RbacPermissionService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ImagesAdminServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.Users',
        'app.Roles',
        'app.Permissions',
    ];

    private ImagesAdminService $service;

    /**
     * Initialize service under test with default collaborators.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ImagesAdminService(
            TableRegistry::getTableLocator()->get('Images'),
            new ImageDeleteService(),
            new ImageEditService(),
            new RbacPermissionService(),
        );
    }

    /**
     * Identity objects with applyScope should be honored before RBAC fallback.
     */
    public function testGetTotalCountUsesIdentityApplyScopeWhenAvailable(): void
    {
        $identity = new class {
            /**
             * Restrict test scope to an empty image set.
             *
             * @param string $action
             * @param mixed $query
             * @return mixed
             */
            public function applyScope(string $action, $query)
            {
                return $query->where(['Images.id <' => 0]);
            }
        };

        $this->assertSame(0, $this->service->getTotalCount($identity));
    }

    /**
     * Common tags should parse comma-separated text and trim blanks.
     */
    public function testBuildCommonEntityTagsParsesCommonTags(): void
    {
        $tags = $this->service->buildCommonEntityTags([
            'common_tags' => 'alpha, beta, ,gamma ',
        ]);

        $this->assertSame(['alpha', 'beta', 'gamma'], $tags);
    }

    /**
     * DataTables payload should include escaped display columns and actions.
     */
    public function testBuildIndexDataTablesResponse(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];

        $response = $this->service->buildIndexDataTablesResponse([
            'draw' => 7,
            'start' => 0,
            'length' => 15,
            'searchValue' => '2',
            'orderDir' => 'asc',
            'orderColumn' => 'original_name',
        ], $adminIdentity);

        $this->assertSame(7, $response['draw']);
        $this->assertGreaterThanOrEqual(1, $response['total']);
        $this->assertGreaterThanOrEqual(1, $response['filtered']);
        $this->assertNotEmpty($response['data']);
        $this->assertStringContainsString('<img', $response['data'][0]['preview']);
        $this->assertStringContainsString('Edit', $response['data'][0]['actions']);
    }

    /**
     * Non-admin metadata updates must ignore owner reassignment.
     */
    public function testUpdateMetadataIgnoresUserIdForNonAdmin(): void
    {
        $bloggerIdentity = [
            'id' => 3,
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ];

        $result = $this->service->updateMetadata(2, [
            'original_name' => 'renamed-by-blogger.png',
            'user_id' => 1,
            'photo_credit' => 'Photographer',
        ], $bloggerIdentity);

        $this->assertTrue($result['success']);

        $image = TableRegistry::getTableLocator()->get('Images')->get(2);
        $this->assertSame('renamed-by-blogger.png', $image->original_name);
        $this->assertSame(3, (int)$image->user_id, 'non-admin should not be able to reassign owner');
    }

    /**
     * Own-scoped update access should reject attempts to load other users' images.
     */
    public function testGetImageByIdThrowsWhenOutsideOwnScope(): void
    {
        $bloggerIdentity = [
            'id' => 3,
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ];

        $this->expectException(RecordNotFoundException::class);
        $this->service->getImageById(1, $bloggerIdentity, 'update');
    }

    /**
     * Bulk delete should enforce RBAC scope for non-admin identities.
     */
    public function testBulkDeleteReturnsZeroForDisallowedIdentity(): void
    {
        $bloggerIdentity = [
            'id' => 3,
            'role' => 'blogger',
            'role_id' => 2,
            'status' => 'active',
            'active' => true,
        ];

        $result = $this->service->bulkDelete([1, 2], $bloggerIdentity);
        $this->assertSame(['deleted' => 0], $result);
    }

    /**
     * Admin identities should be able to bulk delete provided IDs.
     */
    public function testBulkDeleteDeletesForAdminIdentity(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];

        $result = $this->service->bulkDelete([2], $adminIdentity);
        $this->assertSame(['deleted' => 1], $result);
    }

    /**
     * Edit page data should expose owner label and owner-management capability.
     */
    public function testGetEditPageDataForAdminIncludesOwnerMetadata(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];

        $data = $this->service->getEditPageData(1, $adminIdentity);

        $this->assertArrayHasKey('image', $data);
        $this->assertArrayHasKey('users', $data);
        $this->assertArrayHasKey('ownerLabel', $data);
        $this->assertArrayHasKey('canManageImageOwner', $data);
        $this->assertTrue($data['canManageImageOwner']);
        $this->assertSame('admin', (string)$data['ownerLabel']);
    }
}
