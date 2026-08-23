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
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, (int)$result['teamSeason']->team_season_image);
    }
}
