<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StatsService;
use Cake\TestSuite\TestCase;

class StatsServicePersonTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    public array $fixtures = [
        'app.Sports',
        'app.Teams',
        'app.Seasons',
        'app.TeamSeasons',
        'app.TeamSeasonRosters',
        'app.Games',
        'app.Opponents',
        'app.StatBasketSeasonPerson',
        'app.StatBasketGamePerson',
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

    public function testGetPersonSeasonStatsBasketball(): void
    {
        // Basketball sport id is 1 in fixtures
        $stats = $this->service->getPersonSeasonStats(1, 1);

        $this->assertNotNull($stats);
        $this->assertSame(120, (int)($stats->PTS ?? 0));
    }

    public function testGetPersonGameStatsBasketball(): void
    {
        $games = $this->service->getPersonGameStats(1, 1);

        $this->assertIsArray($games);
        $this->assertNotEmpty($games);
        $first = $games[0];
        $this->assertArrayHasKey('game', $first);
        $this->assertArrayHasKey('stats', $first);
        $this->assertNotEmpty($first['stats']);

        // Verify expected stat value from fixture
        $row = $first['stats'][0];
        $this->assertSame(22, (int)($row->PTS ?? 0));
    }
}
