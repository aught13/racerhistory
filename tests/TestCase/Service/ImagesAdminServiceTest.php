<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Table\ImagesImageTagsTable;
use App\Model\Table\ImagesTable;
use App\Model\Table\UsersTable;
use App\Service\GameService;
use App\Service\ImageDeleteService;
use App\Service\ImageEditService;
use App\Service\ImageProcessor;
use App\Service\ImagesAdminService;
use App\Service\ImageStorageService;
use App\Service\ImageTagUiService;
use App\Service\OpponentService;
use App\Service\PersonService;
use App\Service\RbacPermissionService;
use App\Service\SiteService;
use App\Service\TeamSeasonRosterService;
use App\Service\TeamSeasonService;
use App\Service\TeamService;
use App\Service\TeamSportContextService;
use App\Test\TestCase\Support\TaggingServiceFactoryTestTrait;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class ImagesAdminServiceTest extends TestCase
{
    use TaggingServiceFactoryTestTrait;

    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.Persons',
        'app.Opponents',
        'app.Places',
        'app.Teams',
        'app.Sports',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Seasons',
        'app.Games',
        'app.GameTypes',
        'app.Sites',
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

        $taggingServiceFactory = $this->taggingServiceFactory();
        $imagesTable = TableRegistry::getTableLocator()->get('Images');
        assert($imagesTable instanceof ImagesTable);
        $imagesImageTagsTable = TableRegistry::getTableLocator()->get('ImagesImageTags');
        assert($imagesImageTagsTable instanceof ImagesImageTagsTable);
        $storage = new ImageStorageService(new ImageProcessor(), $taggingServiceFactory, $imagesTable);
        $teamSeasonService = new TeamSeasonService();
        $rosterService = new TeamSeasonRosterService(new PersonService(), $teamSeasonService);
        $usersTable = $this->getTableLocator()->get('Users');
        assert($usersTable instanceof UsersTable);

        $this->service = new ImagesAdminService(
            $imagesTable,
            new ImageDeleteService($storage, $taggingServiceFactory, $imagesTable, $imagesImageTagsTable),
            new ImageEditService(new ImageProcessor(), $storage),
            new RbacPermissionService(),
            $taggingServiceFactory,
            $usersTable,
            new TeamService(),
            $teamSeasonService,
            new GameService(),
            new SiteService(),
            new OpponentService(),
            new TeamSportContextService(),
            new ImageTagUiService($rosterService),
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
     * Common team tags use the canonical default display name.
     */
    public function testBuildCommonEntityTagsUsesTeamDisplayName(): void
    {
        $team = TableRegistry::getTableLocator()->get('Teams')->get(1);

        $this->assertSame([
            ['slug' => 'team-1', 'name' => $team->getDisplayName()],
        ], $this->service->buildCommonEntityTags(['team_select' => 1]));
    }

    /**
     * Common opponent tags use the canonical default display name.
     */
    public function testBuildCommonEntityTagsUsesOpponentDisplayName(): void
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        if (!$opponents->getSchema()->hasColumn('opponent_mascot')) {
            $this->markTestSkipped('Test schema does not include opponent_mascot.');
        }
        $opponent = $opponents->get(1);
        $opponent->opponent_mascot = 'Bruins';
        $opponents->saveOrFail($opponent);

        $this->assertSame([
            ['slug' => 'opponent-1', 'name' => 'BEL Bruins'],
        ], $this->service->buildCommonEntityTags(['opponent_select' => 1]));
    }

    /**
     * Common game tags use the game label with its canonical opponent display.
     */
    public function testBuildCommonEntityTagsUsesGameOpponentDisplayName(): void
    {
        $opponents = TableRegistry::getTableLocator()->get('Opponents');
        $opponent = $opponents->get(1);
        $opponent->opponent_mascot = 'Bruins';
        $opponents->saveOrFail($opponent);
        $expected = (new GameService())->getGameTagDisplayLabel(1);

        $this->assertSame([
            ['slug' => 'game-1', 'name' => $expected],
        ], $this->service->buildCommonEntityTags(['game_select' => 1]));
    }

    /**
     * Common team-season tags use the canonical team-and-season label.
     */
    public function testBuildCommonEntityTagsUsesTeamSeasonDisplayName(): void
    {
        $label = (new TeamSeasonService())->getDisplayLabel(1);

        $this->assertSame([
            ['slug' => 'teamseason-1', 'name' => $label],
        ], $this->service->buildCommonEntityTags(['teamseason_select' => 1]));
    }

    /**
     * A selected roster replaces redundant common entity tags but preserves freeform tags.
     */
    public function testBuildCommonEntityTagsSkipsOtherEntitiesWhenRosterSelected(): void
    {
        $data = [
            'person_select' => 1,
            'roster_select' => 1,
            'teamseason_select' => 1,
            'game_select' => 1,
            'site_select' => 1,
            'opponent_select' => 1,
            'team_select' => 1,
            'sport_select' => 1,
            'common_tags' => 'sunset, team photo',
        ];

        $result = $this->service->buildCommonEntityTags($data);
        $slugs = array_column(
            array_values(array_filter($result, static fn($tag): bool => is_array($tag))),
            'slug',
        );

        $this->assertContains('team_season_roster-1', $slugs);
        $this->assertContains('person-1', $slugs);
        $this->assertNotContains('teamseason-1', $slugs);
        $this->assertNotContains('team-1', $slugs);
        $this->assertContains('sunset', $result);
        $this->assertContains('team photo', $result);
    }

    /**
     * Bulk-upload tag arrays are normalized and receive an indexed context tag.
     */
    public function testBuildBulkUploadTagsParsesIndexedArraysAndContext(): void
    {
        $result = $this->service->buildBulkUploadTags(
            ['0' => ['person-1', ' teamseason-1 ']],
            ['0' => 'Media Day'],
            '0',
        );

        $this->assertSame([
            'person-1',
            'teamseason-1',
            ['slug' => 'context-media-day', 'name' => 'Media Day'],
        ], $result);
    }

    /**
     * Bulk-upload tag strings work without indexed form input and blank values are ignored.
     */
    public function testBuildBulkUploadTagsParsesStringsAndIgnoresBlankValues(): void
    {
        $result = $this->service->buildBulkUploadTags('alpha, beta, ', '  ', '0');

        $this->assertSame(['alpha', 'beta'], $result);
        $this->assertSame([], $this->service->buildBulkUploadTags(
            [1 => ['alpha']],
            [1 => 'Media Day'],
            '2',
        ));
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
