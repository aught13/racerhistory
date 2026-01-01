<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\GameService;
use Cake\TestSuite\TestCase;

class GameServiceTest extends TestCase
{
    /**
     * Fixtures
     * Use extended games fixture to avoid altering global GamesFixture expectations in other tests.
     *
     * NOTE: team_season table name is singular (team_season) per migration; fixture alias app.TeamSeasons maps correctly.
     *
     * @var array
     */
    public array $fixtures = [
        'app.GamesExtended',
        'app.TeamSeasons',
        'app.Teams',
        'app.Seasons',
        'app.Sports',
        'app.GameTypes',
        'app.Opponents',
        'app.Places',
        'app.Sites',
    ];

    protected GameService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new GameService();
    }

    /**
     * Basic pagination: length limit respected, counts accurate.
     */
    public function testBuildGamesDataTablePagination(): void
    {
        $result = $this->service->buildGamesDataTable([
            'start' => 0,
            'length' => 2,
            'searchValue' => '',
        ]);

        $this->assertSame(3, $result['recordsTotal'], 'Total records should be 3');
        $this->assertSame(3, $result['recordsFiltered'], 'Filtered should match total without search');
        $this->assertCount(2, $result['data'], 'Data length should respect pagination length=2');
        // Ensure required keys present in first row
        $first = $result['data'][0];
        $this->assertArrayHasKey('game_date', $first);
        $this->assertArrayHasKey('hrn', $first);
        $this->assertArrayHasKey('score', $first);
    }

    /**
     * Global search filters results by partial match (date, opponent, etc.).
     */
    public function testBuildGamesDataTableGlobalSearch(): void
    {
        // Search for a specific game_date substring
        $result = $this->service->buildGamesDataTable([
            'start' => 0,
            'length' => 25,
            'searchValue' => '2025-01-16',
        ]);

        $this->assertSame(3, $result['recordsTotal']);
        $this->assertSame(1, $result['recordsFiltered']);
        $this->assertCount(1, $result['data']);
        $this->assertSame('2025-01-16', $result['data'][0]['game_date']);
    }

    /**
     * SearchBuilder criteria: HRN mapping ('H' => 1) filters correctly.
     */
    public function testBuildGamesDataTableSearchBuilderCriteria(): void
    {
        $searchBuilder = [
            'logic' => 'AND',
            'criteria' => [
                [
                    'origData' => '3', // hrn
                    'condition' => '=',
                    'value1' => 'H', // maps to 1
                ],
            ],
        ];

        $result = $this->service->buildGamesDataTable([
            'start' => 0,
            'length' => 25,
            'searchBuilder' => $searchBuilder,
        ]);

        $this->assertSame(3, $result['recordsTotal']);
        $this->assertSame(2, $result['recordsFiltered']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('H', $result['data'][0]['hrn']);
    }

    /**
     * bulkDeleteGames deletes provided valid IDs and returns metadata.
     */
    public function testBulkDeleteGamesValid(): void
    {
        $before = $this->fetchTable('Games')->find()->count();
        $this->assertSame(3, $before, 'Precondition: 3 games exist');

        $result = $this->service->bulkDeleteGames(['1', '2']);

        $this->assertSame(2, $result['deleted']);
        $this->assertNotNull($result['teamSeasonId']);
        $after = $this->fetchTable('Games')->find()->count();
        $this->assertSame(1, $after, 'Two deletions should leave 1 record');
    }

    /**
     * bulkDeleteGames with empty IDs returns zero deleted.
     */
    public function testBulkDeleteGamesEmpty(): void
    {
        $result = $this->service->bulkDeleteGames([]);
        $this->assertSame(0, $result['deleted']);
        $this->assertNull($result['teamSeasonId']);
    }

    /**
     * bulkDeleteGames partial failure: one valid, one invalid ID.
     */
    public function testBulkDeleteGamesPartialFailure(): void
    {
        $result = $this->service->bulkDeleteGames(['2', '999']);
        $this->assertSame(1, $result['deleted']);
        $remaining = $this->fetchTable('Games')->find()->count();
        $this->assertSame(2, $remaining, 'One record deleted should leave 2');
    }

    /**
     * Test getGameWithAssociations loads full associations
     */
    public function testGetGameWithAssociations(): void
    {
        $game = $this->service->getGameWithAssociations(1);

        $this->assertNotNull($game);
        $this->assertEquals(1, $game->id);
        $this->assertNotNull($game->team_season);
        $this->assertNotNull($game->opponent);
    }

    /**
     * Test getGameWithAssociations throws exception for invalid ID
     */
    public function testGetGameWithAssociationsInvalidId(): void
    {
        $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
        $this->service->getGameWithAssociations(999);
    }

    /**
     * Test loadGameEavValues returns empty array when no EAV data
     */
    public function testLoadGameEavValuesEmpty(): void
    {
        $values = $this->service->loadGameEavValues(1);
        $this->assertIsArray($values);
    }

    /**
     * Test getFormLists returns valid data structure
     */
    public function testGetFormLists(): void
    {
        $lists = $this->service->getFormLists();

        $this->assertArrayHasKey('opponents', $lists);
        $this->assertArrayHasKey('gameTypes', $lists);
        $this->assertArrayHasKey('places', $lists);
        $this->assertArrayHasKey('sites', $lists);

        $this->assertIsArray($lists['opponents']);
        $this->assertIsArray($lists['gameTypes']);
        $this->assertIsArray($lists['places']);
        $this->assertIsArray($lists['sites']);
    }

    /**
     * Test getSitesByPlace filters sites correctly
     */
    public function testGetSitesByPlace(): void
    {
        $sites = $this->service->getSitesByPlace(1);
        $this->assertIsArray($sites);
    }

    public function testSearchGamesForSelectUsesHrnPunctuation(): void
    {
        $results = $this->service->searchGamesForSelect('Belmont', 1, 25);
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        $labels = array_map(fn($g) => (string)($g['label'] ?? ''), $results);

        $this->assertContains('Los Angeles Lakers Vs Belmont (2025-01-15) 80-70', $labels);
        $this->assertContains('Los Angeles Lakers vs Belmont (2025-01-16) 65-72', $labels);
    }

    /**
     * Test calculateWinLoss with team win
     */
    public function testCalculateWinLossTeamWin(): void
    {
        $data = [
            'pts_mur' => 85,
            'pts_opp' => 78,
        ];

        $result = $this->service->calculateWinLoss($data);
        $this->assertEquals(1, $result['w']);
        $this->assertEquals(0, $result['l']);
    }

    /**
     * Test calculateWinLoss with team loss
     */
    public function testCalculateWinLossTeamLoss(): void
    {
        $data = [
            'pts_mur' => 70,
            'pts_opp' => 85,
        ];

        $result = $this->service->calculateWinLoss($data);
        $this->assertEquals(0, $result['w']);
        $this->assertEquals(1, $result['l']);
    }

    /**
     * Test calculateWinLoss with tie
     */
    public function testCalculateWinLossTie(): void
    {
        $data = [
            'pts_mur' => 80,
            'pts_opp' => 80,
        ];

        $result = $this->service->calculateWinLoss($data);
        $this->assertEquals(1, $result['w']);
        $this->assertEquals(1, $result['l']);
    }

    /**
     * Test getTeamSeasonAndSportsLists returns valid structure
     */
    public function testGetTeamSeasonAndSportsLists(): void
    {
        $lists = $this->service->getTeamSeasonAndSportsLists();

        $this->assertArrayHasKey('teamSeasonList', $lists);
        $this->assertArrayHasKey('sports', $lists);
        $this->assertIsArray($lists['teamSeasonList']);
    }
}
