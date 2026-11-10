<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Game;
use App\Service\GameListViewService;
use App\Service\GameService;
use Cake\TestSuite\TestCase;

class GameListViewServiceTest extends TestCase
{
    /**
     * Verify game display fields are added without replacing a saved margin.
     *
     * @return void
     */
    public function testEnrichGamesAddsDisplayFieldsAndPreservesExistingMargin(): void
    {
        $gameService = $this->getMockBuilder(GameService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResultFlag', 'getPlaceName', 'getPlaceState', 'getSiteName'])
            ->getMock();
        $gameService->expects($this->exactly(3))
            ->method('getResultFlag')
            ->willReturnOnConsecutiveCalls('W', 'L', null);
        $gameService->expects($this->exactly(3))
            ->method('getPlaceName')
            ->willReturn('Murray State');
        $gameService->expects($this->exactly(3))
            ->method('getPlaceState')
            ->willReturn('Kentucky');
        $gameService->expects($this->exactly(3))
            ->method('getSiteName')
            ->willReturn('CFSB Center');

        $games = [
            new Game(['hrn' => 1, 'pts_mur' => 80, 'pts_opp' => 70]),
            new Game(['hrn' => 3, 'pts_mur' => 65, 'pts_opp' => 66, 'margin' => 9]),
            new Game(['hrn' => 0, 'pts_mur' => null, 'pts_opp' => null]),
        ];

        $result = (new GameListViewService($gameService))->enrichGames($games);

        $this->assertSame($games, $result);
        $this->assertSame('W', $games[0]->result_flag);
        $this->assertSame('Murray State', $games[0]->place_city);
        $this->assertSame('Kentucky', $games[0]->place_state);
        $this->assertSame('CFSB Center', $games[0]->site_name);
        $this->assertSame('Vs', $games[0]->opponent_prefix);
        $this->assertSame(10, $games[0]->margin);
        $this->assertSame('L', $games[1]->result_flag);
        $this->assertSame('vs', $games[1]->opponent_prefix);
        $this->assertSame(9, $games[1]->margin);
        $this->assertNull($games[2]->result_flag);
        $this->assertSame('@', $games[2]->opponent_prefix);
        $this->assertSame(0, $games[2]->margin);
    }
}
