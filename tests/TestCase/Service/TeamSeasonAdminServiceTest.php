<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TeamSeasonAdminService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class TeamSeasonAdminServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Teams',
        'app.Seasons',
        'app.Sports',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Sites',
        'app.Places',
        'app.Persons',
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.RoleDataScopes',
    ];

    private TeamSeasonAdminService $service;

    /**
     * Initialize service under test.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new TeamSeasonAdminService();
    }

    /**
     * Team selectors on admin team-season pages use the default team display.
     */
    public function testTeamOptionsUseDefaultTeamDisplay(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];
        $team = $this->getTableLocator()->get('Teams')->get(1);

        $indexData = $this->service->getIndexData($adminIdentity);
        $addData = $this->service->getAddFormData(identity: $adminIdentity);
        $editData = $this->service->getEditFormData(1, $adminIdentity);

        $this->assertSame($team->getDisplayName(), $indexData['teams'][1]);
        $this->assertSame($team->getDisplayName(), $addData['teams'][1]);
        $this->assertSame($team->getDisplayName(), $editData['teams'][1]);
    }

    /**
     * Identifier sanitizer should keep only positive integer-like values.
     */
    public function testSanitizeIdentifierList(): void
    {
        $actual = $this->service->sanitizeIdentifierList(['', null, '1', 'x', '02', 3, -1]);
        $this->assertSame([1, 2, 3], $actual);
    }

    /**
     * Delete should return false when caller lacks delete scope.
     */
    public function testDeleteTeamSeasonReturnsFalseWhenIdentityCannotDelete(): void
    {
        $contributorIdentity = [
            'id' => 5,
            'role' => 'contributor',
            'role_id' => 4,
            'status' => 'active',
            'active' => true,
        ];

        $this->assertFalse($this->service->deleteTeamSeason(1, $contributorIdentity));
    }

    /**
     * Delete should succeed for admin identities.
     */
    public function testDeleteTeamSeasonSucceedsForAdminIdentity(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];

        $this->assertTrue($this->service->deleteTeamSeason(1, $adminIdentity));

        $count = TableRegistry::getTableLocator()->get('TeamSeasons')
            ->find()
            ->where(['id' => 1])
            ->count();
        $this->assertSame(0, $count);
    }

    /**
     * Empty bulk payload should no-op.
     */
    public function testBulkDeleteTeamSeasonsReturnsZeroForEmptyInput(): void
    {
        $this->assertSame(0, $this->service->bulkDeleteTeamSeasons([]));
    }

    /**
     * Bulk delete should no-op when RBAC scope rejects all IDs.
     */
    public function testBulkDeleteTeamSeasonsReturnsZeroWhenScopeRejectsIds(): void
    {
        $contributorIdentity = [
            'id' => 5,
            'role' => 'contributor',
            'role_id' => 4,
            'status' => 'active',
            'active' => true,
        ];

        $deleted = $this->service->bulkDeleteTeamSeasons(['1', '2'], $contributorIdentity);
        $this->assertSame(0, $deleted);
    }

    /**
     * Bulk delete should remove allowed IDs for admin identities.
     */
    public function testBulkDeleteTeamSeasonsDeletesAllowedIdsForAdmin(): void
    {
        $adminIdentity = [
            'id' => 1,
            'role' => 'admin',
            'role_id' => 1,
            'status' => 'active',
            'active' => true,
        ];

        $deleted = $this->service->bulkDeleteTeamSeasons(['1', '2', 'bad'], $adminIdentity);
        $this->assertSame(2, $deleted);
    }

    /**
     * Save-new flow should normalize numeric image IDs for ORM type safety.
     */
    public function testSaveNewTeamSeasonNormalizesImageInput(): void
    {
        $result = $this->service->saveNewTeamSeason([
            'team_id' => 1,
            'season_id' => 1,
            'semester' => 1,
            'team_season_image' => '1',
        ], identity: [
            'id' => 1,
            'role_id' => 1,
            'role' => 'admin',
            'status' => 'active',
            'active' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, (int)$result['teamSeason']->team_season_image);
    }

    /**
     * Ensure adjacent TeamSeason navigation does not expose another team's season.
     */
    public function testPreviousNextNavigationRespectsTeamGrant(): void
    {
        $seasons = $this->getTableLocator()->get('Seasons');
        $teamSeasons = $this->getTableLocator()->get('TeamSeasons');
        $nearerSeason = $seasons->newEntity(['start' => 2024, 'end' => 2025]);
        $seasons->saveOrFail($nearerSeason);
        $teamSeasons->saveOrFail($teamSeasons->newEntity([
            'team_id' => 3,
            'season_id' => $nearerSeason->id,
            'semester' => 1,
        ]));

        $scopes = $this->getTableLocator()->get('RoleDataScopes');
        $scopes->deleteAll(['role_id' => 4]);
        $scopes->saveOrFail($scopes->newEntity([
            'role_id' => 4,
            'scope_type' => 'team',
            'team_id' => 1,
        ]));
        $identity = [
            'id' => 4,
            'role_id' => 4,
            'role' => 'contributor',
            'status' => 'active',
            'active' => true,
        ];

        $viewData = $this->service->getViewData(1, $identity);

        $this->assertNotNull($viewData['nextTeamSeason']);
        $this->assertSame(1, (int)$viewData['nextTeamSeason']->team_id);
        $this->assertSame('Admin/basketball_season_stats', $viewData['adminSeasonStatsElement']);
    }

    /**
     * Unsupported sports should not expose an admin season stats element.
     *
     * @return void
     */
    public function testViewDataOmitsStatsForUnsupportedSport(): void
    {
        $teamSeasons = $this->getTableLocator()->get('TeamSeasons');
        $teamSeason = $teamSeasons->newEntity([
            'team_id' => 3,
            'season_id' => 3,
            'semester' => 1,
        ]);
        $teamSeasons->saveOrFail($teamSeason);

        $adminIdentity = [
            'id' => 1,
            'role_id' => 1,
            'role' => 'admin',
            'status' => 'active',
            'active' => true,
        ];
        $viewData = $this->service->getViewData((int)$teamSeason->id, $adminIdentity);

        $this->assertNull($viewData['adminSeasonStatsElement']);
        $this->assertNull($viewData['playerStats']);
    }
}
