<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Game;
use App\Model\Entity\Sport;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use App\Service\GameEavUiService;
use App\Service\GameService;
use App\Service\GameViewService;
use App\Service\SportConfigService;
use App\Service\StatsService;
use Cake\TestSuite\TestCase;

class GameViewServiceTest extends TestCase
{
    /**
     * Tests get view data with sport config.
     */
    public function testGetViewDataWithSportConfig(): void
    {
        $gameId = 123;

        $sport = new Sport(['id' => 1, 'sport_name' => 'Basketball']);
        $team = new Team(['id' => 10, 'sport_id' => 1, 'sport' => $sport]);
        $teamSeason = new TeamSeason(['id' => 20, 'team' => $team]);
        $game = new Game(['id' => $gameId, 'team_season' => $teamSeason]);

        $rawEav = ['period_1_mur' => '35'];
        $mappedEav = ['period_1_mur' => '35', 'period_1_team' => '35'];

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameWithAssociations', 'loadGameEavValues'])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameWithAssociations')
            ->with($gameId)
            ->willReturn($game);
        $gameService->expects($this->once())
            ->method('loadGameEavValues')
            ->with($gameId)
            ->willReturn($rawEav);

        $gameEavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mapLegacyKeys'])
            ->getMock();
        $gameEavUi->expects($this->once())
            ->method('mapLegacyKeys')
            ->with($rawEav)
            ->willReturn($mappedEav);

        $statsService = $this->getMockBuilder(StatsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getGameStats'])
            ->getMock();
        $statsService->expects($this->once())
            ->method('getGameStats')
            ->with($gameId)
            ->willReturn([
                'teamBoxStats' => ['FGM' => 1],
                'opponentBoxStats' => ['FGM' => 2],
                'teamPeriodStats' => [],
                'opponentPeriodStats' => [],
                'playerStats' => [],
                'opponentPlayerStats' => [],
                'teamTeamStats' => (object)['id' => 1],
                'opponentTeamStats' => (object)['id' => 2],
                'hasPeriodStats' => true,
            ]);

        $sportConfigService = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllFieldLabels'])
            ->getMock();
        $sportConfigService->expects($this->once())
            ->method('getAllFieldLabels')
            ->with(1)
            ->willReturn(['FGM' => 'Field Goals Made']);

        $service = new GameViewService($gameService, $sportConfigService, $statsService, $gameEavUi);
        $result = $service->getViewData($gameId);

        $this->assertSame($game, $result['game']);
        $this->assertSame($mappedEav, $result['eav']);
        $this->assertTrue($result['hasSportConfig']);
        $this->assertTrue($result['hasPeriodStats']);
        $this->assertSame(['FGM' => 'Field Goals Made'], $result['fieldLabels']);
        $this->assertSame(['FGM' => 1], $result['teamBoxStats']);
    }

    /**
     * Tests get view data without sport config uses defaults.
     */
    public function testGetViewDataWithoutSportConfigUsesDefaults(): void
    {
        $gameId = 123;

        $game = new Game(['id' => $gameId]);

        $rawEav = ['official_1' => 'Ref A'];
        $mappedEav = $rawEav;

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameWithAssociations', 'loadGameEavValues'])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameWithAssociations')
            ->with($gameId)
            ->willReturn($game);
        $gameService->expects($this->once())
            ->method('loadGameEavValues')
            ->with($gameId)
            ->willReturn($rawEav);

        $gameEavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mapLegacyKeys'])
            ->getMock();
        $gameEavUi->expects($this->once())
            ->method('mapLegacyKeys')
            ->with($rawEav)
            ->willReturn($mappedEav);

        $statsService = $this->getMockBuilder(StatsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getGameStats'])
            ->getMock();
        $statsService->expects($this->never())
            ->method('getGameStats');

        $sportConfigService = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllFieldLabels'])
            ->getMock();
        $sportConfigService->expects($this->never())
            ->method('getAllFieldLabels');

        $service = new GameViewService($gameService, $sportConfigService, $statsService, $gameEavUi);
        $result = $service->getViewData($gameId);

        $this->assertSame($game, $result['game']);
        $this->assertSame($mappedEav, $result['eav']);
        $this->assertFalse($result['hasSportConfig']);
        $this->assertFalse($result['hasPeriodStats']);
        $this->assertSame([], $result['fieldLabels']);
        $this->assertSame([], $result['teamBoxStats']);
        $this->assertSame([], $result['opponentBoxStats']);
    }
}
