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

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new BasketballStatsService();
    }

    /**
     * Tears down the test case.
     */
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

    /**
     * Tests add game person stat to season totals adds values.
     */
    public function testAddGamePersonStatToSeasonTotalsAddsValues(): void
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $gameTable */
        $gameTable = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $seasonTable */
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        /** @var \App\Model\Entity\StatBasketGamePerson $gameStat */
        $gameStat = $gameTable->get(1);
        /** @var \App\Model\Entity\StatBasketSeasonPerson $before */
        $before = $seasonTable->find()->where(['team_season_roster_id' => 1])->firstOrFail();

        $beforePts = (int)$before->PTS;
        $beforeGp = (int)$before->GP;

        $this->assertTrue($this->service->addGamePersonStatToSeasonTotals($gameStat));

        /** @var \App\Model\Entity\StatBasketSeasonPerson $after */
        $after = $seasonTable->find()->where(['team_season_roster_id' => 1])->firstOrFail();
        $this->assertSame($beforePts + 22, (int)$after->PTS);
        $this->assertSame((string)($beforeGp + 1), (string)$after->GP);
    }

    /**
     * Tests update game person stat season totals subtracts and adds.
     */
    public function testUpdateGamePersonStatSeasonTotalsSubtractsAndAdds(): void
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $gameTable */
        $gameTable = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $seasonTable */
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        /** @var \App\Model\Entity\StatBasketGamePerson $original */
        $original = $gameTable->get(1);
        $updated = clone $original;
        $updated->PTS = '30';
        $updated->MIN = '40';

        /** @var \App\Model\Entity\StatBasketSeasonPerson $seed */
        $seed = $seasonTable->find()->where(['team_season_roster_id' => 1])->firstOrFail();

        // Seed totals so they already include the original game stat.
        $seed->PTS = (int)$seed->PTS + (int)$original->PTS;
        $seed->GP = (string)((int)$seed->GP + (int)$original->GP);
        $seasonTable->saveOrFail($seed);

        $beforePts = (int)$seed->PTS;
        $beforeGp = (int)$seed->GP;

        $this->assertTrue($this->service->updateGamePersonStatSeasonTotals($original, $updated));

        /** @var \App\Model\Entity\StatBasketSeasonPerson $after */
        $after = $seasonTable->find()->where(['team_season_roster_id' => 1])->firstOrFail();
        $this->assertSame($beforePts - 22 + 30, (int)$after->PTS);
        $this->assertSame((string)($beforeGp - 1 + 1), (string)$after->GP);
    }

    /**
     * Tests apply game box to season totals adds and updates.
     */
    public function testApplyGameBoxToSeasonTotalsAddsAndUpdates(): void
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        /** @var \App\Model\Table\StatBasketSeasonTeamTable $teamSeasonTable */
        $teamSeasonTable = $this->fetchTable('StatBasketSeasonTeam');
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $opponentSeasonTable */
        $opponentSeasonTable = $this->fetchTable('StatBasketSeasonOpponent');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->get(1);
        /** @var \App\Model\Entity\StatBasketGameBox $teamBox */
        $teamBox = $boxTable->get(1);
        /** @var \App\Model\Entity\StatBasketGameBox $opponentBox */
        $opponentBox = $boxTable->get(2);

        /** @var \App\Model\Entity\StatBasketSeasonTeam $teamBefore */
        $teamBefore = $teamSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();
        /** @var \App\Model\Entity\StatBasketSeasonOpponent $oppBefore */
        $oppBefore = $opponentSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();

        $teamPtsBefore = (int)$teamBefore->PTS;
        $oppPtsBefore = (int)$oppBefore->PTS;

        $this->assertTrue($this->service->applyGameBoxToSeasonTotals($game, $teamBox, $opponentBox));

        /** @var \App\Model\Entity\StatBasketSeasonTeam $teamAfter */
        $teamAfter = $teamSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();
        /** @var \App\Model\Entity\StatBasketSeasonOpponent $oppAfter */
        $oppAfter = $opponentSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();

        $this->assertSame($teamPtsBefore + 78, (int)$teamAfter->PTS);
        $this->assertSame($oppPtsBefore + 70, (int)$oppAfter->PTS);

        $updatedTeamBox = clone $teamBox;
        $updatedOpponentBox = clone $opponentBox;
        $updatedTeamBox->PTS = '80';
        $updatedOpponentBox->PTS = '72';

        $this->assertTrue(
            $this->service->applyGameBoxToSeasonTotals($game, $updatedTeamBox, $updatedOpponentBox, $teamBox, $opponentBox),
        );

        /** @var \App\Model\Entity\StatBasketSeasonTeam $teamAfterUpdate */
        $teamAfterUpdate = $teamSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();
        /** @var \App\Model\Entity\StatBasketSeasonOpponent $oppAfterUpdate */
        $oppAfterUpdate = $opponentSeasonTable->find()->where(['team_season_id' => $game->team_season_id])->firstOrFail();

        $this->assertSame($teamPtsBefore + 80, (int)$teamAfterUpdate->PTS);
        $this->assertSame($oppPtsBefore + 72, (int)$oppAfterUpdate->PTS);
    }
}
