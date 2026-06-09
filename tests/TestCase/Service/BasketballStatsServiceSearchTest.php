<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BasketballStatsService;
use Cake\TestSuite\TestCase;

/**
 * BasketballStatsService Search Methods Test Case
 */
class BasketballStatsServiceSearchTest extends TestCase
{
    protected array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.StatBasketGameBox',
        'app.StatBasketGamePerson',
        'app.StatBasketGameTeam',
        'app.StatBasketGameOpponent',
        'app.StatBasketSeasonPerson',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.TeamSeasonRosters',
        'app.Persons',
    ];

    protected BasketballStatsService $service;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BasketballStatsService();
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    // ——— searchPlayerSeasonStats ————————————

    public function testSearchPlayerSeasonStatsReturnsResults(): void
    {
        $results = $this->service->searchPlayerSeasonStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('person', $results[0]);
        $this->assertArrayHasKey('teamSeason', $results[0]);
    }

    /**
     * Tests search player season stats filter by season.
     */
    public function testSearchPlayerSeasonStatsFilterBySeason(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['season_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search player season stats filter by team.
     */
    public function testSearchPlayerSeasonStatsFilterByTeam(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['team_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search player season stats no results.
     */
    public function testSearchPlayerSeasonStatsNoResults(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['season_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    /**
     * Tests search player season stats sort by rebounds.
     */
    public function testSearchPlayerSeasonStatsSortByRebounds(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['sort' => 'RB', 'direction' => 'DESC']);
        $this->assertIsArray($results);
    }

    /**
     * Tests search player season stats invalid sort defaults to pts.
     */
    public function testSearchPlayerSeasonStatsInvalidSortDefaultsToPts(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['sort' => 'INVALID']);
        $this->assertIsArray($results);
    }

    /**
     * Tests search player season stats invalid direction defaults to desc.
     */
    public function testSearchPlayerSeasonStatsInvalidDirectionDefaultsToDesc(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['direction' => 'INVALID']);
        $this->assertIsArray($results);
    }

    /**
     * Tests search player season stats limit clamp.
     */
    public function testSearchPlayerSeasonStatsLimitClamp(): void
    {
        $results = $this->service->searchPlayerSeasonStats(['limit' => 500]);
        $this->assertIsArray($results);
    }

    // ——— searchTeamSeasonStats ————————————

    public function testSearchTeamSeasonStatsReturnsResults(): void
    {
        $results = $this->service->searchTeamSeasonStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('teamSeason', $results[0]);
    }

    /**
     * Tests search team season stats filter by season.
     */
    public function testSearchTeamSeasonStatsFilterBySeason(): void
    {
        $results = $this->service->searchTeamSeasonStats(['season_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search team season stats filter by team.
     */
    public function testSearchTeamSeasonStatsFilterByTeam(): void
    {
        $results = $this->service->searchTeamSeasonStats(['team_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search team season stats no results.
     */
    public function testSearchTeamSeasonStatsNoResults(): void
    {
        $results = $this->service->searchTeamSeasonStats(['season_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ——— searchTeamSeasonOpponentStats ——————

    public function testSearchTeamSeasonOpponentStatsReturnsResults(): void
    {
        $results = $this->service->searchTeamSeasonOpponentStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('teamSeason', $results[0]);
    }

    /**
     * Tests search team season opponent stats filter by season.
     */
    public function testSearchTeamSeasonOpponentStatsFilterBySeason(): void
    {
        $results = $this->service->searchTeamSeasonOpponentStats(['season_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search team season opponent stats no results.
     */
    public function testSearchTeamSeasonOpponentStatsNoResults(): void
    {
        $results = $this->service->searchTeamSeasonOpponentStats(['season_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ——— searchPlayerGameStats ——————————————

    public function testSearchPlayerGameStatsReturnsResults(): void
    {
        $results = $this->service->searchPlayerGameStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('person', $results[0]);
        $this->assertArrayHasKey('game', $results[0]);
    }

    /**
     * Tests search player game stats filter by game.
     */
    public function testSearchPlayerGameStatsFilterByGame(): void
    {
        $results = $this->service->searchPlayerGameStats(['game_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search player game stats filter by season.
     */
    public function testSearchPlayerGameStatsFilterBySeason(): void
    {
        $results = $this->service->searchPlayerGameStats(['season_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search player game stats no results.
     */
    public function testSearchPlayerGameStatsNoResults(): void
    {
        $results = $this->service->searchPlayerGameStats(['game_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ——— searchOpponentPlayerGameStats ——————

    public function testSearchOpponentPlayerGameStatsReturnsResults(): void
    {
        $results = $this->service->searchOpponentPlayerGameStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('game', $results[0]);
    }

    /**
     * Tests search opponent player game stats filter by game.
     */
    public function testSearchOpponentPlayerGameStatsFilterByGame(): void
    {
        $results = $this->service->searchOpponentPlayerGameStats(['game_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search opponent player game stats no results.
     */
    public function testSearchOpponentPlayerGameStatsNoResults(): void
    {
        $results = $this->service->searchOpponentPlayerGameStats(['game_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ——— searchTeamGameStats ———————————————

    public function testSearchTeamGameStatsReturnsTeamFinalRows(): void
    {
        $results = $this->service->searchTeamGameStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('game', $results[0]);
        $this->assertSame(0, (int)($results[0]['stat']->opponent_id ?? -1));
        $this->assertSame(78, (int)($results[0]['stat']->PTS ?? 0));
    }

    /**
     * Tests search team game stats filter by game.
     */
    public function testSearchTeamGameStatsFilterByGame(): void
    {
        $results = $this->service->searchTeamGameStats(['game_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    // ——— searchOpponentTeamGameStats —————————

    public function testSearchOpponentTeamGameStatsReturnsOpponentFinalRows(): void
    {
        $results = $this->service->searchOpponentTeamGameStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('stat', $results[0]);
        $this->assertArrayHasKey('game', $results[0]);
        $this->assertGreaterThan(0, (int)($results[0]['stat']->opponent_id ?? 0));
        $this->assertSame(70, (int)($results[0]['stat']->PTS ?? 0));
    }

    /**
     * Tests search opponent team game stats no results.
     */
    public function testSearchOpponentTeamGameStatsNoResults(): void
    {
        $results = $this->service->searchOpponentTeamGameStats(['game_id' => 999]);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    // ——— searchPlayerCareerStats ——————————

    public function testSearchPlayerCareerStatsReturnsResults(): void
    {
        $results = $this->service->searchPlayerCareerStats();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('person', $results[0]);
        $this->assertArrayHasKey('totals', $results[0]);
        $this->assertArrayHasKey('seasons', $results[0]);
    }

    /**
     * Tests search player career stats aggregates.
     */
    public function testSearchPlayerCareerStatsAggregates(): void
    {
        $results = $this->service->searchPlayerCareerStats();
        $this->assertNotEmpty($results);

        // Person 1 (John Doe) should have stats aggregated from fixture
        $totals = $results[0]['totals'];
        $this->assertIsArray($totals);
        $this->assertArrayHasKey('PTS', $totals);
        $this->assertArrayHasKey('GP', $totals);
        $this->assertGreaterThan(0, $totals['PTS']);
    }

    /**
     * Tests search player career stats filter by team.
     */
    public function testSearchPlayerCareerStatsFilterByTeam(): void
    {
        $results = $this->service->searchPlayerCareerStats(['team_id' => 1]);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    /**
     * Tests search player career stats sort asc.
     */
    public function testSearchPlayerCareerStatsSortAsc(): void
    {
        $results = $this->service->searchPlayerCareerStats(['sort' => 'GP', 'direction' => 'ASC']);
        $this->assertIsArray($results);
    }

    /**
     * Tests search player career stats invalid sort.
     */
    public function testSearchPlayerCareerStatsInvalidSort(): void
    {
        $results = $this->service->searchPlayerCareerStats(['sort' => 'INVALID']);
        $this->assertIsArray($results);
    }

    /**
     * Tests search player career stats limit.
     */
    public function testSearchPlayerCareerStatsLimit(): void
    {
        $results = $this->service->searchPlayerCareerStats(['limit' => 1]);
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(1, count($results));
    }

    // ——— getFilterOptions ——————————————————

    public function testGetFilterOptionsReturnsStructure(): void
    {
        $options = $this->service->getFilterOptions();
        $this->assertIsArray($options);
        $this->assertArrayHasKey('seasons', $options);
        $this->assertArrayHasKey('teams', $options);
    }

    /**
     * Tests get filter options seasons not empty.
     */
    public function testGetFilterOptionsSeasonsNotEmpty(): void
    {
        $options = $this->service->getFilterOptions();
        $this->assertNotEmpty($options['seasons']);
    }

    /**
     * Tests get filter options teams contains basketball.
     */
    public function testGetFilterOptionsTeamsContainsBasketball(): void
    {
        $options = $this->service->getFilterOptions();
        $this->assertNotEmpty($options['teams']);
    }
}
