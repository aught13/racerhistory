<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\AdminDataScopeService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

class AdminDataScopeServiceTest extends TestCase
{
    /**
     * @var array<int,string>
     */
    public array $fixtures = [
        'app.RoleDataScopes',
        'app.Roles',
        'app.Permissions',
        'app.Users',
        'app.Teams',
        'app.Sports',
        'app.TeamSeasons',
        'app.Games',
        'app.StatBasketGameBox',
        'app.StatBasketSeasonTeam',
    ];

    private AdminDataScopeService $service;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminDataScopeService();
    }

    /**
     * @return void
     */
    public function testAdminIsUnrestricted(): void
    {
        $admin = [
            'id' => 1,
            'role_id' => 1,
            'role' => 'admin',
            'status' => 'active',
            'active' => true,
        ];

        $this->assertNull($this->service->getAllowedTeamIds($admin));
        $this->assertTrue($this->service->canAccessTeam($admin, 3));
        $this->assertTrue($this->service->canCreateTeamForSport($admin, 'football'));
    }

    /**
     * @return void
     */
    public function testLegacyRoleAllGrantPreservesUnrestrictedAccess(): void
    {
        $editor = $this->identityForRole(3);

        $this->assertNull($this->service->getAllowedTeamIds($editor));
    }

    /**
     * @return void
     */
    public function testSportGrantIncludesAllTeamsInThatSport(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'sport', 'sport_key' => 'football'],
        ]);
        $contributor = $this->identityForRole(4);

        $this->assertSame([3], $this->service->getAllowedTeamIds($contributor));
        $this->assertTrue($this->service->canAccessTeam($contributor, 3));
        $this->assertFalse($this->service->canAccessTeam($contributor, 1));
        $this->assertTrue($this->service->canCreateTeamForSport($contributor, 'football'));
        $this->assertFalse($this->service->canCreateTeamForSport($contributor, 'basketball'));
    }

    /**
     * @return void
     */
    public function testTeamGrantDoesNotAllowCreatingAnotherTeamInItsSport(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 3],
        ]);
        $contributor = $this->identityForRole(4);

        $this->assertSame([3], $this->service->getAllowedTeamIds($contributor));
        $this->assertTrue($this->service->canAccessTeam($contributor, 3));
        $this->assertFalse($this->service->canAccessTeam($contributor, 1));
        $this->assertFalse($this->service->canCreateTeamForSport($contributor, 'football'));
    }

    /**
     * @return void
     */
    public function testMissingScopeRowsDenyTeamOwnedData(): void
    {
        $this->replaceRoleScopes(4, []);

        $this->assertSame([], $this->service->getAllowedTeamIds($this->identityForRole(4)));
    }

    /**
     * @return void
     */
    public function testTeamOwnedQueriesApplyAllowedTeamIds(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 1],
        ]);
        $contributor = $this->identityForRole(4);
        $teams = TableRegistry::getTableLocator()->get('Teams')->find();
        $teamSeasons = TableRegistry::getTableLocator()->get('TeamSeasons')->find();

        $this->assertSame([1], $this->service->scopeTeamOwnedQuery($contributor, 'Teams', $teams)
            ->select(['Teams.id'])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList());
        $this->assertSame([1, 2], $this->service->scopeTeamOwnedQuery($contributor, 'TeamSeasons', $teamSeasons)
            ->select(['TeamSeasons.id'])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList());
    }

    /**
     * @return void
     */
    public function testTeamSeasonAndGameChecksFollowTheTeamGrant(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 1],
        ]);
        $contributor = $this->identityForRole(4);

        $this->assertTrue($this->service->canAccessTeamSeason($contributor, 1));
        $this->assertFalse($this->service->canAccessTeamSeason($contributor, 999));
        $this->assertSame([1, 2], $this->service->getAllowedTeamSeasonIds($contributor));
    }

    /**
     * @return void
     */
    public function testBasketballStatRowsFollowTheirParentScope(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 1],
        ]);
        $contributor = $this->identityForRole(4);

        $this->assertTrue($this->service->canAccessGameStatRow($contributor, 'StatBasketGameBox', 1));
        $this->assertTrue($this->service->canAccessSeasonStatRow($contributor, 'StatBasketSeasonTeam', 1));

        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 3],
        ]);
        $this->service->clearRoleScopeCache(4);
        $this->assertFalse($this->service->canAccessGameStatRow($contributor, 'StatBasketGameBox', 1));
        $this->assertFalse($this->service->canAccessSeasonStatRow($contributor, 'StatBasketSeasonTeam', 1));
        $this->assertFalse($this->service->canAccessGameStatRow($contributor, 'UnknownStats', 1));
    }

    /**
     * @return void
     */
    public function testStatAdminRouteResolvesParentType(): void
    {
        $this->replaceRoleScopes(4, [
            ['scope_type' => 'team', 'team_id' => 1],
        ]);
        $contributor = $this->identityForRole(4);

        $this->assertTrue($this->service->canAccessStatAdminRequest(
            $contributor,
            'StatBasketGameBox',
            'gameBox',
            1,
        ));
        $this->assertTrue($this->service->canAccessStatAdminRequest(
            $contributor,
            'StatBasketSeasonPerson',
            'add',
            1,
        ));
        $this->assertTrue($this->service->canAccessStatAdminRequest(
            $contributor,
            'StatBasketGameBox',
            'unknownAction',
            1,
        ));
    }

    /**
     * @param int $roleId Role identifier.
     * @return array{id:int,role_id:int,role:string,status:string,active:bool}
     */
    private function identityForRole(int $roleId): array
    {
        return [
            'id' => 100 + $roleId,
            'role_id' => $roleId,
            'role' => 'contributor',
            'status' => 'active',
            'active' => true,
        ];
    }

    /**
     * @param int $roleId Role identifier.
     * @param array<int,array<string,int|string>> $scopes Scope records.
     * @return void
     */
    private function replaceRoleScopes(int $roleId, array $scopes): void
    {
        $table = TableRegistry::getTableLocator()->get('RoleDataScopes');
        $table->deleteAll(['role_id' => $roleId]);
        foreach ($scopes as $scope) {
            $table->saveOrFail($table->newEntity(['role_id' => $roleId] + $scope));
        }
    }
}
