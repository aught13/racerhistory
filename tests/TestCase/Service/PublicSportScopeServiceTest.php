<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PublicSportScopeService;
use Cake\TestSuite\TestCase;

class PublicSportScopeServiceTest extends TestCase
{
    /**
     * @var array<int,string>
     */
    public array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

    private PublicSportScopeService $service;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PublicSportScopeService();
    }

    /**
     * @return void
     */
    public function testMissingScopeResolvesToAllSportsAndTeams(): void
    {
        $this->assertSame([
            'valid' => true,
            'error' => null,
            'sportKey' => null,
            'teamId' => null,
        ], $this->service->resolve([]));
    }

    /**
     * The only team with a result is selected for public game browsing.
     *
     * @return void
     */
    public function testGamesScopeSelectsOnlyTeamWithResults(): void
    {
        $gamesTable = $this->fetchTable('Games');
        $teamSeason = $this->fetchTable('TeamSeasons')->newEntity([
            'team_id' => 2,
            'season_id' => 1,
            'semester' => 1,
        ]);
        $this->fetchTable('TeamSeasons')->saveOrFail($teamSeason);
        $gamesTable->saveOrFail($gamesTable->newEntity([
            'team_season_id' => $teamSeason->id,
            'game_date' => '2024-01-21',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
        ]));

        $this->assertSame([
            'valid' => true,
            'error' => null,
            'sportKey' => 'basketball',
            'teamId' => 1,
        ], $this->service->resolveGamesScope(['sport' => 'basketball', 'team' => 'all']));
    }

    /**
     * Multiple teams with results leave the game scope unselected.
     *
     * @return void
     */
    public function testGamesScopeDoesNotSelectWhenMultipleTeamsHaveResults(): void
    {
        $teamSeasons = $this->fetchTable('TeamSeasons');
        $teamSeason = $teamSeasons->newEntity([
            'team_id' => 2,
            'season_id' => 1,
            'semester' => 1,
        ]);
        $teamSeasons->saveOrFail($teamSeason);

        $gamesTable = $this->fetchTable('Games');
        $gamesTable->saveOrFail($gamesTable->newEntity([
            'team_season_id' => $teamSeason->id,
            'game_date' => '2024-01-21',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
            'w' => 'W',
            'pts_mur' => '10',
            'pts_opp' => '5',
        ]));

        $scope = $this->service->resolveGamesScope(['sport' => 'basketball', 'team' => 'all']);

        $this->assertTrue($scope['valid']);
        $this->assertNull($scope['teamId']);
        $this->assertSame('basketball', $scope['sportKey']);
    }

    /**
     * The only supported stats sport is selected automatically.
     *
     * @return void
     */
    public function testStatsScopeSelectsTheOnlySupportedSport(): void
    {
        $scope = $this->service->resolveStatsScope([], ['basketball']);

        $this->assertTrue($scope['valid']);
        $this->assertSame('basketball', $scope['sportKey']);
        $this->assertNull($scope['teamId']);
    }

    /**
     * Multiple supported stats sports still require a selection.
     *
     * @return void
     */
    public function testStatsScopeRequiresSelectionWhenMultipleSportsAreSupported(): void
    {
        $scope = $this->service->resolveStatsScope([], ['basketball', 'football']);

        $this->assertTrue($scope['valid']);
        $this->assertNull($scope['sportKey']);
    }

    /**
     * @return void
     */
    public function testSelectedTeamImpliesItsSport(): void
    {
        $this->assertSame([
            'valid' => true,
            'error' => null,
            'sportKey' => 'football',
            'teamId' => 3,
        ], $this->service->resolve(['team' => '3']));
    }

    /**
     * @return void
     */
    public function testRejectsTeamOutsideSelectedSport(): void
    {
        $scope = $this->service->resolve([
            'sport' => 'basketball',
            'team' => '3',
        ]);

        $this->assertFalse($scope['valid']);
        $this->assertSame('team_sport_mismatch', $scope['error']);
        $this->assertNull($scope['sportKey']);
        $this->assertNull($scope['teamId']);
    }

    /**
     * @return void
     */
    public function testRejectsUnknownTeamInsteadOfBroadeningScope(): void
    {
        $scope = $this->service->resolve(['team' => '999']);

        $this->assertFalse($scope['valid']);
        $this->assertSame('invalid_team', $scope['error']);
        $this->assertNull($scope['sportKey']);
        $this->assertNull($scope['teamId']);
    }

    /**
     * @return void
     */
    public function testListsOnlySportsWithTeams(): void
    {
        $this->assertSame([
            'basketball' => 'Basketball',
            'football' => 'Football',
        ], $this->service->getAvailableSportOptions());
    }

    /**
     * @return void
     */
    public function testTeamOptionsCarryTheirSportAndDisambiguateAllSports(): void
    {
        $teamOptions = $this->service->getTeamOptions();

        $this->assertSame([
            'label' => "Giants Men's New York Giants (Football)",
            'sportKey' => 'football',
        ], $teamOptions[3]);
    }

    /**
     * @return void
     */
    public function testTeamOptionsCanBeRestrictedToOneSport(): void
    {
        $teamOptions = $this->service->getTeamOptions('football');

        $this->assertSame([3], array_keys($teamOptions));
        $this->assertSame("Giants Men's New York Giants", $teamOptions[3]['label']);
    }
}
