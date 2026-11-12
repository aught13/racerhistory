<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Game;
use App\Service\GameSearchService;
use Cake\TestSuite\TestCase;

class GameSearchServiceScopeTest extends TestCase
{
    /**
     * @var array<int,string>
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
    ];

    private GameSearchService $service;

    private Game $unscoredGame;

    private Game $partialScoreGame;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->service = new GameSearchService();
        $teamSeason = $this->getTableLocator()->get('TeamSeasons')->newEntity([
            'team_id' => 3,
            'season_id' => 1,
            'semester' => 1,
        ]);
        $this->getTableLocator()->get('TeamSeasons')->saveOrFail($teamSeason);
        $gamesTable = $this->getTableLocator()->get('Games');
        $gamesTable->saveOrFail(
            $gamesTable->newEntity([
                'team_season_id' => $teamSeason->id,
                'game_date' => '2024-02-02',
                'game_type_id' => 1,
                'opponent_id' => 1,
                'place_id' => 1,
                'site_id' => 1,
                'hrn' => 1,
                'w' => 'W',
                'pts_mur' => '25',
                'pts_opp' => '24',
            ]),
        );
        $this->unscoredGame = $gamesTable->saveOrFail($gamesTable->newEntity([
            'team_season_id' => $teamSeason->id,
            'game_date' => '2024-02-03',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
        ]));
        $this->partialScoreGame = $gamesTable->saveOrFail($gamesTable->newEntity([
            'team_season_id' => $teamSeason->id,
            'game_date' => '2024-02-04',
            'game_type_id' => 1,
            'opponent_id' => 1,
            'place_id' => 1,
            'site_id' => 1,
            'hrn' => 1,
            'pts_mur' => '30',
        ]));
    }

    /**
     * @return void
     */
    public function testAllGamesIncludesEverySportByDefault(): void
    {
        $games = $this->service->allGames();
        $this->assertCount(5, $games);
        $gameIds = array_map(static fn(Game $game): int => (int)$game->id, $games);
        $this->assertNotContains((int)$this->unscoredGame->id, $gameIds);
        $this->assertNotContains((int)$this->partialScoreGame->id, $gameIds);
        $this->assertFalse($this->service->hasResult($this->unscoredGame));
        $this->assertFalse($this->service->hasResult($this->partialScoreGame));
    }

    /**
     * @return void
     */
    public function testSportScopeFiltersGames(): void
    {
        $this->service->setPublicScope('football', null);

        $games = $this->service->allGames();

        $this->assertCount(1, $games);
        $this->assertSame(3, (int)$games[0]->team_season->team->id);
    }

    /**
     * @return void
     */
    public function testTeamScopeFiltersGames(): void
    {
        $this->service->setPublicScope('football', 3);

        $games = $this->service->allGames();

        $this->assertCount(1, $games);
        $this->assertSame(3, (int)$games[0]->team_season->team->id);
    }

    /**
     * Unscored games do not split or extend a public winning streak.
     *
     * @return void
     */
    public function testStreaksIgnoreGamesWithoutResults(): void
    {
        $this->service->setPublicScope('football', 3);

        $streaks = $this->service->streaks('W');

        $this->assertCount(1, $streaks);
        $this->assertSame(1, $streaks[0]['length']);
        $this->assertSame('2024-02-02', $streaks[0]['start_date']);
        $this->assertSame('2024-02-02', $streaks[0]['end_date']);
    }

    /**
     * Opponent filter options use the default short-name and mascot display.
     */
    public function testOpponentOptionsUseDefaultDisplayName(): void
    {
        $opponents = $this->getTableLocator()->get('Opponents');
        $opponent = $opponents->get(1);
        if ($opponents->getSchema()->hasColumn('opponent_mascot')) {
            $opponent->opponent_mascot = 'Bruins';
        }
        $opponents->saveOrFail($opponent);

        $this->assertSame(
            $opponents->get(1)->getDisplayName(),
            $this->service->getOpponentsList()[1],
        );
    }

    /**
     * Series search results provide a canonical display label and search mascots.
     */
    public function testSeriesOpponentSearchUsesDefaultDisplayName(): void
    {
        $opponents = $this->getTableLocator()->get('Opponents');
        if (!$opponents->getSchema()->hasColumn('opponent_mascot')) {
            $this->markTestSkipped('Test schema does not include opponent_mascot.');
        }
        $opponent = $opponents->get(1);
        $opponent->opponent_mascot = 'Bruins';
        $opponents->saveOrFail($opponent);

        $result = $this->service->searchSeriesOpponents('Bruins');

        $this->assertNotEmpty($result['rows']);
        $this->assertSame('BEL Bruins', $result['rows'][0]['opponent_display_name']);
        $this->assertSame(5, (int)$result['rows'][0]['games_count']);
    }
}
