<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\SportStatRegistry;
use App\Model\Table\GameEavTable;
use App\Model\Table\SportStatRegistryTable;
use App\Service\SportConfigService;
use Cake\ORM\Query;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\GameEavTable Test Case
 */
class GameEavTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\GameEavTable
     */
    protected $GameEav;

    /**
     * Mock registry table
     *
     * @var \App\Model\Table\SportStatRegistryTable|MockObject
     */
    protected $mockRegistryTable;

    /**
     * Mock sport config service
     *
     * @var \App\Service\SportConfigService|MockObject
     */
    protected $mockSportConfigService;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->mockRegistryTable = $this->createMock(SportStatRegistryTable::class);
        $this->mockSportConfigService = $this->createMock(SportConfigService::class);

        // Create a GameEav table with null connection to avoid DB calls
        $this->GameEav = new GameEavTable([
            'connection' => null,
            'alias' => 'GameEav',
            'table' => 'game_eav',
        ]);

        // Inject mocked dependencies
        $this->GameEav
            ->setSportStatRegistry($this->mockRegistryTable)
            ->setSportConfigService($this->mockSportConfigService);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->GameEav, $this->mockRegistryTable, $this->mockSportConfigService);

        parent::tearDown();
    }

    /**
     * Test getStatTablesForSport method with mocked registry
     *
     * @return void
     */
    public function testGetStatTablesForSport(): void
    {
        // Create mock registry entities
        $mockRegistry1 = $this->createMock(SportStatRegistry::class);
        $mockRegistry1->method('__get')->willReturnMap([
            ['id', 1],
            ['context', 'game'],
            ['entity_type', 'team'],
            ['table_name', 'stat_basket_game_team'],
            ['display_name', 'Basketball Game Team Stats'],
            ['mapped_fields', ['FGM' => ['label' => 'Field Goals Made', 'type' => 'numeric']]],
            ['primary_key', 'id'],
        ]);

        $mockRegistry2 = $this->createMock(SportStatRegistry::class);
        $mockRegistry2->method('__get')->willReturnMap([
            ['id', 2],
            ['context', 'game'],
            ['entity_type', 'player'],
            ['table_name', 'stat_basket_game_person'],
            ['display_name', 'Basketball Game Player Stats'],
            ['mapped_fields', ['PTS' => ['label' => 'Points', 'type' => 'numeric']]],
            ['primary_key', 'custom_id'],
        ]);

        // Mock result set
        $mockResults = [$mockRegistry1, $mockRegistry2];

        // Mock query
        $mockQuery = $this->createMock(Query::class);
        $mockQuery->method('find')->willReturnSelf();
        $mockQuery->method('toArray')->willReturn($mockResults);

        // Configure registry table mock
        $this->mockRegistryTable->method('find')
            ->with('bySport', ['sport_id' => 1])
            ->willReturn($mockQuery);

        // Test the method
        $result = $this->GameEav->getStatTablesForSport(1);

        // Verify expected result structure
        $expected = [
            'game.team' => [
                'table_name' => 'stat_basket_game_team',
                'display_name' => 'Basketball Game Team Stats',
                'field_mapping' => ['FGM' => ['label' => 'Field Goals Made', 'type' => 'numeric']],
                'primary_key' => 'id',
                'registry_id' => 1,
            ],
            'game.player' => [
                'table_name' => 'stat_basket_game_person',
                'display_name' => 'Basketball Game Player Stats',
                'field_mapping' => ['PTS' => ['label' => 'Points', 'type' => 'numeric']],
                'primary_key' => 'custom_id',
                'registry_id' => 2,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test the fallback to SportConfigService when no database records exist
     *
     * @return void
     */
    public function testGetStatTablesForSportFallback(): void
    {
        // Mock empty result set for registry table
        $mockQuery = $this->createMock(Query::class);
        $mockQuery->method('find')->willReturnSelf();
        $mockQuery->method('toArray')->willReturn([]);

        // Configure registry table mock to return empty results
        $this->mockRegistryTable->method('find')
            ->with('bySport', ['sport_id' => 1])
            ->willReturn($mockQuery);

        // Configure sport config service mocks
        $this->mockSportConfigService->method('getAllStatTables')
            ->with(1)
            ->willReturn([
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'player' => 'stat_basket_game_person',
                ],
                'season' => [
                    'team' => 'stat_basket_season_team',
                ],
            ]);

        // We need to update our approach since getSportName is protected
        // Let's create a custom mock that extends SportConfigService and exposes the method
        $mockSportConfigService = $this->getMockBuilder(SportConfigService::class)
            ->onlyMethods(['getAllStatTables', 'getAllFieldLabels', 'getStatFields'])
            ->getMock();

        // Replace our original mock
        $this->GameEav->setSportConfigService($mockSportConfigService);

        // Configure the methods on our new mock
        $mockSportConfigService->method('getAllStatTables')
            ->with(1)
            ->willReturn([
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'player' => 'stat_basket_game_person',
                ],
                'season' => [
                    'team' => 'stat_basket_season_team',
                ],
            ]);

        $mockSportConfigService->method('getAllFieldLabels')
            ->with(1)
            ->willReturn([
                'FGM' => 'Field Goals Made',
                'PTS' => 'Points',
            ]);

        $mockSportConfigService->method('getStatFields')
            ->willReturnMap([
                [1, 'team', ['FGM', 'FGA']],
                [1, 'player', ['PTS', 'AST']],
            ]);

        // Test the method
        $result = $this->GameEav->getStatTablesForSport(1);

        // Verify it contains expected keys
        $this->assertArrayHasKey('game.team', $result);
        $this->assertArrayHasKey('game.player', $result);
        $this->assertArrayHasKey('season.team', $result);

        // Check a specific entry's structure
        $this->assertEquals('stat_basket_game_team', $result['game.team']['table_name']);
        $this->assertEquals('Basketball Game Team Stats', $result['game.team']['display_name']);
        $this->assertIsArray($result['game.team']['field_mapping']);
        $this->assertArrayHasKey('FGM', $result['game.team']['field_mapping']);
        $this->assertEquals('id', $result['game.team']['primary_key']);
        $this->assertNull($result['game.team']['registry_id']);
    }

    /**
     * Test getStatTablesForSport with context filter
     *
     * @return void
     */
    public function testGetStatTablesForSportWithContextFilter(): void
    {
        // Mock empty result set for registry table
        $mockQuery = $this->createMock(Query::class);
        $mockQuery->method('find')->willReturnSelf();
        $mockQuery->method('toArray')->willReturn([]);

        // Configure registry table mock
        $this->mockRegistryTable->method('find')
            ->willReturn($mockQuery);

        // Create a custom mock for SportConfigService
        $mockSportConfigService = $this->getMockBuilder(SportConfigService::class)
            ->onlyMethods(['getAllStatTables', 'getAllFieldLabels', 'getStatFields'])
            ->getMock();

        // Replace our original mock
        $this->GameEav->setSportConfigService($mockSportConfigService);

        // Configure sport config service mocks
        $mockSportConfigService->method('getAllStatTables')
            ->willReturn([
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'player' => 'stat_basket_game_person',
                ],
                'season' => [
                    'team' => 'stat_basket_season_team',
                ],
            ]);

        $mockSportConfigService->method('getAllFieldLabels')
            ->willReturn([]);

        $mockSportConfigService->method('getStatFields')
            ->willReturn([]);

        // Test with game context filter
        $gameResult = $this->GameEav->getStatTablesForSport(1, 'game');

        // Should only include game tables
        $this->assertArrayHasKey('game.team', $gameResult);
        $this->assertArrayHasKey('game.player', $gameResult);
        $this->assertArrayNotHasKey('season.team', $gameResult);

        // Test with season context filter
        $seasonResult = $this->GameEav->getStatTablesForSport(1, 'season');

        // Should only include season tables
        $this->assertArrayNotHasKey('game.team', $seasonResult);
        $this->assertArrayNotHasKey('game.player', $seasonResult);
        $this->assertArrayHasKey('season.team', $seasonResult);
    }
}
