<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\GameEavTable;
use App\Service\SportConfigService;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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
    protected GameEavTable $GameEav;

    /**
     * Mock sport config service
     *
     * @var \App\Service\SportConfigService|MockObject
     */
    protected SportConfigService&MockObject $mockSportConfigService;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->mockSportConfigService = $this->createMock(SportConfigService::class);

        // Create a GameEav table with null connection to avoid DB calls
        $this->GameEav = new GameEavTable([
            'connection' => null,
            'alias' => 'GameEav',
            'table' => 'game_eav',
        ]);

        // Inject mocked dependencies
        $this->GameEav->setSportConfigService($this->mockSportConfigService);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->GameEav, $this->mockSportConfigService);

        parent::tearDown();
    }

    /**
     * Test stat table mapping generated from SportConfigService values.
     *
     * @return void
     */
    public function testGetStatTablesForSport(): void
    {
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

        $this->mockSportConfigService->method('getKeyById')
            ->with(1)
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getDefaultSportKey')
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getSportDisplayName')
            ->with('basketball')
            ->willReturn('Basketball');

        $this->mockSportConfigService->method('getAllFieldLabels')
            ->with(1)
            ->willReturn([
                'FGM' => 'Field Goals Made',
                'PTS' => 'Points',
            ]);

        $this->mockSportConfigService->method('getStatFields')
            ->willReturnMap([
                [1, 'team', ['FGM', 'FGA']],
                [1, 'player', ['PTS', 'AST']],
                [1, 'opponent', []],
            ]);

        $result = $this->GameEav->getStatTablesForSport(1);

        $this->assertArrayHasKey('game.team', $result);
        $this->assertArrayHasKey('game.player', $result);
        $this->assertArrayHasKey('season.team', $result);

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
        $this->mockSportConfigService->method('getAllStatTables')
            ->willReturn([
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'player' => 'stat_basket_game_person',
                ],
                'season' => [
                    'team' => 'stat_basket_season_team',
                ],
            ]);

        $this->mockSportConfigService->method('getKeyById')
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getDefaultSportKey')
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getSportDisplayName')
            ->willReturn('Basketball');

        $this->mockSportConfigService->method('getAllFieldLabels')
            ->willReturn([]);

        $this->mockSportConfigService->method('getStatFields')
            ->willReturn([]);

        $gameResult = $this->GameEav->getStatTablesForSport(1, 'game');
        $this->assertArrayHasKey('game.team', $gameResult);
        $this->assertArrayHasKey('game.player', $gameResult);
        $this->assertArrayNotHasKey('season.team', $gameResult);

        $seasonResult = $this->GameEav->getStatTablesForSport(1, 'season');
        $this->assertArrayNotHasKey('game.team', $seasonResult);
        $this->assertArrayNotHasKey('game.player', $seasonResult);
        $this->assertArrayHasKey('season.team', $seasonResult);
    }

    /**
     * Test entity type filter.
     *
     * @return void
     */
    public function testGetStatTablesForSportWithEntityTypeFilter(): void
    {
        $this->mockSportConfigService->method('getAllStatTables')
            ->willReturn([
                'game' => [
                    'team' => 'stat_basket_game_team',
                    'player' => 'stat_basket_game_person',
                ],
            ]);

        $this->mockSportConfigService->method('getKeyById')
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getDefaultSportKey')
            ->willReturn('basketball');

        $this->mockSportConfigService->method('getSportDisplayName')
            ->willReturn('Basketball');

        $this->mockSportConfigService->method('getAllFieldLabels')
            ->willReturn([]);

        $this->mockSportConfigService->method('getStatFields')
            ->willReturn([]);

        $teamResult = $this->GameEav->getStatTablesForSport(1, null, 'team');
        $this->assertArrayHasKey('game.team', $teamResult);
        $this->assertArrayNotHasKey('game.player', $teamResult);
    }
}
