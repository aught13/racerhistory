<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StatsService;
use Cake\TestSuite\TestCase;

/**
 * StatsService Search Delegation Tests
 */
class StatsServiceSearchTest extends TestCase
{
    public array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Persons',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.StatBasketSeasonPerson',
        'app.StatBasketSeasonTeam',
        'app.StatBasketSeasonOpponent',
        'app.StatBasketGamePerson',
        'app.StatBasketGameBox',
        'app.StatBasketGameOpponent',
        'app.StatBasketGameTeam',
    ];

    protected StatsService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new StatsService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    // ——— getSportIdByName ————————————————

    public function testGetSportIdByNameBasketball(): void
    {
        $id = $this->service->getSportIdByName('basketball');
        $this->assertSame(1, $id);
    }

    public function testGetSportIdByNameCaseInsensitive(): void
    {
        $id = $this->service->getSportIdByName('Basketball');
        $this->assertSame(1, $id);
    }

    public function testGetSportIdByNameInvalid(): void
    {
        $id = $this->service->getSportIdByName('curling');
        $this->assertNull($id);
    }

    // ——— Search delegation ————————————————

    public function testSearchPlayerSeasonStatsDelegates(): void
    {
        // Sport ID 1 = Basketball
        $results = $this->service->searchPlayerSeasonStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testSearchPlayerSeasonStatsUnsupportedSport(): void
    {
        // Sport ID 3 = Baseball (no stats service)
        $results = $this->service->searchPlayerSeasonStats(3);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchTeamSeasonStatsDelegates(): void
    {
        $results = $this->service->searchTeamSeasonStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testSearchTeamSeasonOpponentStatsDelegates(): void
    {
        $results = $this->service->searchTeamSeasonOpponentStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testSearchPlayerGameStatsDelegates(): void
    {
        $results = $this->service->searchPlayerGameStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testSearchOpponentPlayerGameStatsDelegates(): void
    {
        $results = $this->service->searchOpponentPlayerGameStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testSearchPlayerCareerStatsDelegates(): void
    {
        $results = $this->service->searchPlayerCareerStats(1);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }

    public function testGetFilterOptionsDelegates(): void
    {
        $options = $this->service->getFilterOptions(1);
        $this->assertIsArray($options);
        $this->assertArrayHasKey('seasons', $options);
        $this->assertArrayHasKey('teams', $options);
    }

    public function testGetFilterOptionsUnsupportedSport(): void
    {
        $options = $this->service->getFilterOptions(3);
        $this->assertIsArray($options);
        $this->assertEmpty($options['seasons']);
        $this->assertEmpty($options['teams']);
    }

    // ——— Invalid sport ID returns empty ————

    public function testSearchPlayerSeasonStatsInvalidSportId(): void
    {
        $results = $this->service->searchPlayerSeasonStats(999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchTeamSeasonStatsInvalidSportId(): void
    {
        $results = $this->service->searchTeamSeasonStats(999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchPlayerCareerStatsInvalidSportId(): void
    {
        $results = $this->service->searchPlayerCareerStats(999);
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }
}
