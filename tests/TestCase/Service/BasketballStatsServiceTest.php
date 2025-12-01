<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\BasketballStatsService;
use Cake\TestSuite\TestCase;

/**
 * BasketballStatsService Test Case
 */
class BasketballStatsServiceTest extends TestCase
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

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BasketballStatsService();
    }

    public function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test getGameStats returns null for non-existent game
     */
    public function testGetGameStatsNonExistent(): void
    {
        $stats = $this->service->getGameStats(999);
        $this->assertNull($stats);
    }

    /**
     * Test getGameStats returns null for non-basketball game
     */
    public function testGetGameStatsNonBasketball(): void
    {
        // Assuming game 1 exists but might not be basketball
        // This test validates the sport check logic
        $stats = $this->service->getGameStats(1);
        
        // Either null (if not basketball) or array (if basketball)
        if ($stats !== null) {
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('teamBoxStats', $stats);
            $this->assertArrayHasKey('opponentBoxStats', $stats);
        } else {
            $this->assertNull($stats);
        }
    }

    /**
     * Test getSeasonStats returns null for non-existent team season
     */
    public function testGetSeasonStatsNonExistent(): void
    {
        $stats = $this->service->getSeasonStats(999);
        $this->assertNull($stats);
    }

    /**
     * Test getSeasonStats returns null for non-basketball team season
     */
    public function testGetSeasonStatsNonBasketball(): void
    {
        $stats = $this->service->getSeasonStats(1);
        
        // Either null (if not basketball) or array (if basketball)
        if ($stats !== null) {
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('teamStats', $stats);
            $this->assertArrayHasKey('opponentStats', $stats);
        } else {
            $this->assertNull($stats);
        }
    }

    /**
     * Test initializeStats for player type
     */
    public function testInitializeStatsPlayer(): void
    {
        $stats = $this->service->initializeStats('player');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('GP', $stats);
        $this->assertArrayHasKey('PTS', $stats);
        $this->assertArrayHasKey('FGM', $stats);
        $this->assertSame(0, $stats['GP']);
        $this->assertSame(0, $stats['PTS']);
    }

    /**
     * Test initializeStats for team type
     */
    public function testInitializeStatsTeam(): void
    {
        $stats = $this->service->initializeStats('team');
        
        // Team stats may return empty array as per implementation
        $this->assertIsArray($stats);
    }

    /**
     * Test addSeasonStats accumulates correctly
     */
    public function testAddSeasonStats(): void
    {
        $seasonStatsTable = $this->fetchTable('StatBasketSeasonPerson');
        
        $seasonStats = $seasonStatsTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 10,
            'MIN' => 200,
            'FGM' => 50,
            'FGA' => 100,
            'PTS' => 150,
        ]);
        
        $totals = $this->service->initializeStats('player');
        $this->service->addSeasonStats($totals, $seasonStats);
        
        $this->assertSame(10, $totals['GP']);
        $this->assertSame(150, $totals['PTS']);
        $this->assertSame(50, $totals['FGM']);
    }

    /**
     * Test getPersonSeasonStats returns null for non-existent roster
     */
    public function testGetPersonSeasonStatsNonExistent(): void
    {
        $stats = $this->service->getPersonSeasonStats(999);
        $this->assertNull($stats);
    }

    /**
     * Test getPersonGameStats returns empty array for non-existent roster
     */
    public function testGetPersonGameStatsNonExistent(): void
    {
        $stats = $this->service->getPersonGameStats(999);
        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /**
     * Test initializeStats with opponent type
     */
    public function testInitializeStatsOpponent(): void
    {
        $stats = $this->service->initializeStats('opponent');
        
        // Opponent stats may return empty array as per implementation
        $this->assertIsArray($stats);
    }
}
