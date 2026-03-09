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

    /**
     * Test getGameStats returns null for non-existent game
     */
    public function testGetGameStatsNonExistent(): void
    {
        $stats = $this->service->getGameStats(999);
        $this->assertNull($stats);
    }

    /**
     * Test getSeasonStats returns null for non-existent team season
     */
    public function testGetSeasonStatsNonExistent(): void
    {
        $stats = $this->service->getSeasonStats(999);
        $this->assertNull($stats);
    }

    public function testGetSeasonStatsElementReturnsConfiguredPath(): void
    {
        $element = $this->service->getSeasonStatsElement(1);

        $this->assertSame('Seasons/basketball_season_stats', $element);
    }

    public function testGetSeasonStatsColumnsIncludesPointsColumn(): void
    {
        $seasonStats = $this->service->getSeasonStats(1);

        $this->assertIsArray($seasonStats);

        $columns = $this->service->getSeasonStatsColumns(1, $seasonStats);

        $this->assertIsArray($columns);
        $this->assertArrayHasKey('PTS', $columns);
    }

    /**
     * Test getPersonSeasonStats returns null for non-existent roster
     */
    public function testGetPersonSeasonStatsNonExistent(): void
    {
        $stats = $this->service->getPersonSeasonStats(1, 999);
        $this->assertNull($stats);
    }

    /**
     * Test getPersonGameStats returns empty array for non-existent roster
     */
    public function testGetPersonGameStatsNonExistent(): void
    {
        $stats = $this->service->getPersonGameStats(1, 999);
        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /**
     * Test hasSportSupport with invalid sport ID
     */
    public function testHasSportSupportInvalidId(): void
    {
        $this->assertFalse($this->service->hasSportSupport(999));
    }

    // ——— Stat cell delegation methods ————————

    public function testGetPlayerSeasonStatCellsReturnsExpectedCount(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 10, 'GS' => 8, 'MIN' => 250,
            'FGM' => 45, 'FGA' => 90,
            'TPM' => 12, 'TPA' => 30,
            'FTM' => 18, 'FTA' => 24,
            'ORB' => 15, 'DRB' => 35, 'RB' => 50,
            'AST' => 22, 'STL' => 10, 'BS' => 5,
            'TRN' => 12, 'PF' => 18, 'PTS' => 120,
        ]);

        $cells = $this->service->getPlayerSeasonStatCells(1, $stat);
        $this->assertIsArray($cells);
        $this->assertCount(18, $cells);
        $this->assertSame(10, $cells[0]); // GP
        $this->assertSame(120, $cells[17]); // PTS
    }

    public function testGetTeamSeasonStatCellsReturnsExpectedCount(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity([
            'team_season_roster_id' => 1,
            'GP' => 30, 'MIN' => 1200,
            'FGM' => 400, 'FGA' => 900,
            'TPM' => 100, 'TPA' => 300,
            'FTM' => 200, 'FTA' => 280,
            'ORB' => 150, 'DRB' => 350, 'RB' => 500,
            'AST' => 200, 'STL' => 80, 'BS' => 40,
            'TRN' => 120, 'PF' => 150, 'PTS' => 1100,
        ]);

        $cells = $this->service->getTeamSeasonStatCells(1, $stat);
        $this->assertIsArray($cells);
        $this->assertCount(17, $cells);
        $this->assertSame(30, $cells[0]); // GP
        $this->assertSame(1100, $cells[16]); // PTS
    }

    public function testGetPlayerCareerStatCellsReturnsExpectedCount(): void
    {
        $totals = [
            'GP' => 100, 'GS' => 80, 'MIN' => 2500,
            'FGM' => 450, 'FGA' => 900,
            'TPM' => 120, 'TPA' => 300,
            'FTM' => 180, 'FTA' => 240,
            'ORB' => 150, 'DRB' => 350, 'RB' => 500,
            'AST' => 220, 'STL' => 100, 'BS' => 50,
            'TRN' => 120, 'PF' => 180, 'PTS' => 1200,
        ];

        $cells = $this->service->getPlayerCareerStatCells(1, $totals);
        $this->assertIsArray($cells);
        $this->assertCount(18, $cells);
        $this->assertSame(100, $cells[0]); // GP
        $this->assertSame(1200, $cells[17]); // PTS
    }

    public function testGetTeamGameStatCellsReturnsExpectedCount(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity([
            'team_season_roster_id' => 1,
            'FGM' => 40, 'FGA' => 85,
            'TPM' => 10, 'TPA' => 28,
            'FTM' => 15, 'FTA' => 20,
            'ORB' => 12, 'DRB' => 30, 'RB' => 42,
            'AST' => 18, 'STL' => 8, 'BS' => 4,
            'TRN' => 10, 'PF' => 15, 'PTS' => 105,
        ]);

        $cells = $this->service->getTeamGameStatCells(1, $stat);
        $this->assertIsArray($cells);
        $this->assertCount(15, $cells);
    }

    public function testGetOpponentPlayerNameReturnsString(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity(['team_season_roster_id' => 1, 'name' => 'John Doe']);

        $name = $this->service->getOpponentPlayerName(1, $stat);
        $this->assertIsString($name);
    }

    public function testGetOpponentPlayerGameStatCellsReturnsExpectedCount(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity([
            'team_season_roster_id' => 1,
            'MIN' => 25,
            'FGM' => 8, 'FGA' => 18,
            'TPM' => 2, 'TPA' => 6,
            'FTM' => 4, 'FTA' => 6,
            'ORB' => 3, 'DRB' => 7, 'RB' => 10,
            'AST' => 5, 'STL' => 2, 'BS' => 1,
            'TRN' => 3, 'PF' => 4, 'PTS' => 22,
        ]);

        $cells = $this->service->getOpponentPlayerGameStatCells(1, $stat);
        $this->assertIsArray($cells);
        $this->assertCount(16, $cells);
    }

    public function testStatCellMethodsReturnEmptyForUnsupportedSport(): void
    {
        $statTable = $this->fetchTable('StatBasketSeasonPerson');
        $stat = $statTable->newEntity(['team_season_roster_id' => 1]);

        $this->assertSame([], $this->service->getPlayerSeasonStatCells(999, $stat));
        $this->assertSame([], $this->service->getTeamSeasonStatCells(999, $stat));
        $this->assertSame([], $this->service->getPlayerGameStatCells(999, $stat));
        $this->assertSame([], $this->service->getTeamGameStatCells(999, $stat));
        $this->assertSame([], $this->service->getOpponentPlayerGameStatCells(999, $stat));
        $this->assertSame('', $this->service->getOpponentPlayerName(999, $stat));
        $this->assertSame([], $this->service->getPlayerCareerStatCells(999, []));
    }

    public function testGetSeasonPlayerStatsListReturnsArrayForValidSeason(): void
    {
        $stats = $this->service->getSeasonPlayerStatsList(1);
        $this->assertIsArray($stats);
    }

    public function testGetSeasonPlayerStatsListReturnsEmptyForInvalidSeason(): void
    {
        $stats = $this->service->getSeasonPlayerStatsList(9999);
        $this->assertSame([], $stats);
    }
}
