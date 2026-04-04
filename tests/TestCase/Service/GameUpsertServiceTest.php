<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Game;
use App\Model\Entity\Sport;
use App\Model\Entity\Team;
use App\Model\Entity\TeamSeason;
use App\Model\Table\GamesTable;
use App\Service\GameEavUiService;
use App\Service\GameService;
use App\Service\GameUpsertService;
use App\Service\SportConfigService;
use Cake\ORM\Query\SelectQuery;
use Cake\TestSuite\TestCase;

class GameUpsertServiceTest extends TestCase
{
    public function testProcessAddReturnsErrorsAndViewDataWhenPeriodValidationFails(): void
    {
        $teamSeasonId = 1;

        $game = new Game();

        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => ['scoring_type' => 'cumulative'],
            'eavTemplate' => ['period_1_team' => ['label' => 'Period 1 - Team']],
            'values' => [],
        ];

        $gamesTable = $this->getMockBuilder(GamesTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newEmptyEntity'])
            ->getMock();
        $gamesTable->expects($this->once())
            ->method('newEmptyEntity')
            ->willReturn($game);

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata', 'normalizeAssociatedInlineCreate', 'calculateWinLoss'])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameEavMetadata')
            ->with(null, $teamSeasonId)
            ->willReturn($metadata);
        $gameService->expects($this->once())
            ->method('normalizeAssociatedInlineCreate');
        $gameService->expects($this->once())
            ->method('calculateWinLoss')
            ->willReturnCallback(static fn(array $data): array => $data);

        $sportConfig = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validatePeriodScores'])
            ->getMock();
        $sportConfig->expects($this->once())
            ->method('validatePeriodScores')
            ->with(1, $this->isType('array'))
            ->willReturn(['Team period scores (10) must equal final team score (20)']);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mapLegacyKeys'])
            ->getMock();
        $eavUi->expects($this->once())
            ->method('mapLegacyKeys')
            ->with($this->isType('array'))
            ->willReturn(['period_1_mur' => '10', 'period_1_team' => '10']);

        $service = new GameUpsertService($gamesTable, $gameService, $sportConfig, $eavUi);

        $result = $service->processAdd($teamSeasonId, ['period_1_mur' => '10', 'pts_mur' => '20']);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['flashErrors']);
        $this->assertNull($result['redirect']);

        $viewData = $result['viewData'];
        $this->assertSame($game, $viewData['game']);
        $this->assertSame(1, $viewData['sportId']);
        $this->assertSame('Basketball', $viewData['sportName']);
        $this->assertArrayHasKey('legacyMappedEav', $viewData);
    }

    public function testProcessAddRedirectsToOpponentEditWhenInlineOpponentCreated(): void
    {
        $teamSeasonId = 1;

        $game = new Game(['id' => 99]);

        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => [],
            'eavTemplate' => [],
            'values' => [],
        ];

        $gamesTable = $this->getMockBuilder(GamesTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newEmptyEntity', 'patchEntity', 'save'])
            ->getMock();
        $gamesTable->expects($this->once())
            ->method('newEmptyEntity')
            ->willReturn($game);
        $gamesTable->expects($this->once())
            ->method('patchEntity')
            ->with($game, $this->isType('array'))
            ->willReturn($game);
        $gamesTable->expects($this->once())
            ->method('save')
            ->with($game)
            ->willReturn($game);

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods([
                'getGameEavMetadata',
                'normalizeAssociatedInlineCreate',
                'calculateWinLoss',
                'saveGameEavFromRequest',
            ])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameEavMetadata')
            ->with(null, $teamSeasonId)
            ->willReturn($metadata);
        $gameService->expects($this->once())
            ->method('normalizeAssociatedInlineCreate')
            ->willReturnCallback(static function (array &$data): void {
                $data['opponent_id'] = 555;
            });
        $gameService->expects($this->once())
            ->method('calculateWinLoss')
            ->willReturnCallback(static fn(array $data): array => $data);
        $gameService->expects($this->once())
            ->method('saveGameEavFromRequest')
            ->with(99, $this->isType('array'));

        $sportConfig = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validatePeriodScores'])
            ->getMock();
        $sportConfig->expects($this->once())
            ->method('validatePeriodScores')
            ->willReturn([]);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mapLegacyKeys'])
            ->getMock();
        $eavUi->expects($this->never())->method('mapLegacyKeys');

        $service = new GameUpsertService($gamesTable, $gameService, $sportConfig, $eavUi);

        $result = $service->processAdd($teamSeasonId, [
            'new_opponent' => ['opponent_name' => 'New Opponent'],
            'pts_mur' => 1,
            'pts_opp' => 0,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame(
            [
                'prefix' => 'Admin',
                'controller' => 'Opponents',
                'action' => 'edit',
                555,
            ],
            $result['redirect']
        );
    }

    public function testProcessEditReturnsErrorsAndMergesPostedPeriodFields(): void
    {
        $gameId = 1;

        $sport = new Sport(['id' => 1]);
        $team = new Team(['sport' => $sport]);
        $teamSeason = new TeamSeason(['team' => $team]);
        $game = new Game(['id' => $gameId, 'place_id' => 9, 'team_season' => $teamSeason]);

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['contain', 'where', 'firstOrFail'])
            ->getMock();
        $query->method('contain')->willReturnSelf();
        $query->method('where')->willReturnSelf();
        $query->method('firstOrFail')->willReturn($game);

        $gamesTable = $this->getMockBuilder(GamesTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $gamesTable->expects($this->once())
            ->method('find')
            ->willReturn($query);

        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => [],
            'eavTemplate' => [],
            'values' => ['period_1_team' => '35'],
        ];

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata', 'normalizeAssociatedInlineCreate', 'calculateWinLoss'])
            ->getMock();
        $gameService->expects($this->once())
            ->method('getGameEavMetadata')
            ->with($gameId, null)
            ->willReturn($metadata);
        $gameService->expects($this->once())
            ->method('normalizeAssociatedInlineCreate');
        $gameService->expects($this->once())
            ->method('calculateWinLoss')
            ->willReturnCallback(static fn(array $data): array => $data);

        $sportConfig = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validatePeriodScores'])
            ->getMock();
        $sportConfig->expects($this->once())
            ->method('validatePeriodScores')
            ->with(1, $this->isType('array'))
            ->willReturn(['Opponent period scores (10) must equal final opponent score (20)']);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)
            ->onlyMethods(['mergePostedPeriodAndOvertimeFields', 'mapLegacyKeys'])
            ->getMock();
        $eavUi->expects($this->once())
            ->method('mergePostedPeriodAndOvertimeFields')
            ->with(['period_1_team' => '35'], $this->isType('array'))
            ->willReturn(['period_1_team' => '35', 'period_2_team' => '10']);
        $eavUi->expects($this->once())
            ->method('mapLegacyKeys')
            ->with(['period_1_team' => '35', 'period_2_team' => '10'])
            ->willReturn(['period_1_team' => '35', 'period_2_team' => '10']);

        $service = new GameUpsertService($gamesTable, $gameService, $sportConfig, $eavUi);

        $result = $service->processEdit($gameId, ['period_2_team' => '10', 'pts_mur' => '35']);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['flashErrors']);
        $this->assertNull($result['redirect']);
        $this->assertSame(9, $result['placeId']);
        $this->assertSame($game, $result['viewData']['game']);
    }

    public function testProcessAddPastGameRedirectsToAddResults(): void
    {
        $teamSeasonId = 1;

        $pastDate = new \Cake\I18n\Date('-3 days');
        $game = new Game(['id' => 42, 'game_date' => $pastDate]);

        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => [],
            'eavTemplate' => [],
            'values' => [],
        ];

        $gamesTable = $this->getMockBuilder(GamesTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newEmptyEntity', 'patchEntity', 'save'])
            ->getMock();
        $gamesTable->method('newEmptyEntity')->willReturn($game);
        $gamesTable->method('patchEntity')->willReturn($game);
        $gamesTable->method('save')->willReturn($game);

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata', 'normalizeAssociatedInlineCreate', 'calculateWinLoss', 'saveGameEavFromRequest'])
            ->getMock();
        $gameService->method('getGameEavMetadata')->willReturn($metadata);
        $gameService->method('normalizeAssociatedInlineCreate');
        $gameService->method('calculateWinLoss')->willReturnCallback(static fn(array $d): array => $d);
        $gameService->method('saveGameEavFromRequest');

        $sportConfig = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validatePeriodScores'])
            ->getMock();
        $sportConfig->method('validatePeriodScores')->willReturn([]);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)->getMock();

        $service = new GameUpsertService($gamesTable, $gameService, $sportConfig, $eavUi);
        $result = $service->processAdd($teamSeasonId, ['pts_mur' => 1, 'pts_opp' => 0]);

        $this->assertTrue($result['success']);
        $this->assertSame('addResults', $result['redirect']['action']);
        $this->assertSame(42, $result['gameId']);
        $this->assertStringContainsString('Add results', $result['flashSuccess']);
    }

    public function testProcessAddFutureGameRedirectsToAddAnother(): void
    {
        $teamSeasonId = 5;

        $futureDate = new \Cake\I18n\Date('+7 days');
        $game = new Game(['id' => 43, 'game_date' => $futureDate]);

        $metadata = [
            'sportId' => 1,
            'sportName' => 'Basketball',
            'configs' => [],
            'eavTemplate' => [],
            'values' => [],
        ];

        $gamesTable = $this->getMockBuilder(GamesTable::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['newEmptyEntity', 'patchEntity', 'save'])
            ->getMock();
        $gamesTable->method('newEmptyEntity')->willReturn($game);
        $gamesTable->method('patchEntity')->willReturn($game);
        $gamesTable->method('save')->willReturn($game);

        $gameService = $this->getMockBuilder(GameService::class)
            ->onlyMethods(['getGameEavMetadata', 'normalizeAssociatedInlineCreate', 'calculateWinLoss', 'saveGameEavFromRequest'])
            ->getMock();
        $gameService->method('getGameEavMetadata')->willReturn($metadata);
        $gameService->method('normalizeAssociatedInlineCreate');
        $gameService->method('calculateWinLoss')->willReturnCallback(static fn(array $d): array => $d);
        $gameService->method('saveGameEavFromRequest');

        $sportConfig = $this->getMockBuilder(SportConfigService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validatePeriodScores'])
            ->getMock();
        $sportConfig->method('validatePeriodScores')->willReturn([]);

        $eavUi = $this->getMockBuilder(GameEavUiService::class)->getMock();

        $service = new GameUpsertService($gamesTable, $gameService, $sportConfig, $eavUi);
        $result = $service->processAdd($teamSeasonId, []);

        $this->assertTrue($result['success']);
        $this->assertSame('add', $result['redirect']['action']);
        $this->assertSame($teamSeasonId, $result['redirect']['?']['team_season_id']);
        $this->assertSame(43, $result['gameId']);
        $this->assertStringContainsString('Add another', $result['flashSuccess']);
    }
}
