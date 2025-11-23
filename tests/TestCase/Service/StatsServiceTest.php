<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\StatsService;
use Cake\TestSuite\TestCase;

/**
 * StatsServiceTest
 *
 * Tests for the generic stats service coordinator that delegates to sport-specific services.
 */
class StatsServiceTest extends TestCase
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
        'app.Games',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
        'app.StatBasketSeasonPerson',
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

    /**
     * Test hasSportSupport returns true for basketball
     */
    public function testHasSportSupportBasketball(): void
    {
        // Basketball (sport ID 1) should be supported
        $this->assertTrue($this->service->hasSportSupport(1));
    }

    /**
     * Test hasSportSupport returns false for unsupported sports
     */
    public function testHasSportSupportUnsupported(): void
    {
        // Create a test sport without a service
        $sportsTable = $this->fetchTable('Sports');
        $sport = $sportsTable->newEntity(['sport_name' => 'Hockey', 'sport_abbr' => 'HOC']);
        $sportsTable->save($sport);

        $this->assertFalse($this->service->hasSportSupport($sport->id));
    }

    /**
     * Test getSupportedSports returns expected list
     */
    public function testGetSupportedSports(): void
    {
        $supported = $this->service->getSupportedSports();

        $this->assertIsArray($supported);
        $this->assertContains('basketball', $supported);
    }

    /**
     * Test initializeStats returns zeroed basketball stats
     */
    public function testInitializeStatsBasketball(): void
    {
        $stats = $this->service->initializeStats(1, 'player');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('GP', $stats);
        $this->assertArrayHasKey('PTS', $stats);
        $this->assertSame(0, $stats['GP']);
        $this->assertSame(0, $stats['PTS']);
    }

    /**
     * Test addSeasonStats accumulates basketball stats correctly
     */
    public function testAddSeasonStatsBasketball(): void
    {
        // Create mock season stats
        $seasonStatsTable = $this->fetchTable('StatBasketSeasonPerson');
        $seasonStats = $seasonStatsTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 10,
            'GS' => 5,
            'MIN' => 200,
            'FGM' => 50,
            'FGA' => 100,
            'TPM' => 20,
            'TPA' => 50,
            'FTM' => 30,
            'FTA' => 40,
            'ORB' => 10,
            'DRB' => 20,
            'RB' => 30,
            'AST' => 15,
            'STL' => 5,
            'BS' => 3,
            'TRN' => 8,
            'PF' => 12,
            'TF' => 1,
            'PTS' => 150,
        ]);

        $totals = $this->service->initializeStats(1, 'player');
        $this->service->addSeasonStats(1, $totals, $seasonStats);

        $this->assertSame(10, $totals['GP']);
        $this->assertSame(150, $totals['PTS']);
        $this->assertSame(50, $totals['FGM']);
    }

    /**
     * Test addSeasonStats handles multiple seasons correctly
     */
    public function testAddSeasonStatsMultipleSeasons(): void
    {
        $seasonStatsTable = $this->fetchTable('StatBasketSeasonPerson');

        $season1 = $seasonStatsTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 10,
            'PTS' => 100,
            'AST' => 20,
        ]);

        $season2 = $seasonStatsTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 15,
            'PTS' => 150,
            'AST' => 30,
        ]);

        $totals = $this->service->initializeStats(1, 'player');
        $this->service->addSeasonStats(1, $totals, $season1);
        $this->service->addSeasonStats(1, $totals, $season2);

        $this->assertSame(25, $totals['GP'], 'GP should be sum of both seasons');
        $this->assertSame(250, $totals['PTS'], 'PTS should be sum of both seasons');
        $this->assertSame(50, $totals['AST'], 'AST should be sum of both seasons');
    }
}
