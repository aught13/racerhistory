<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Opponent;
use App\Model\Entity\Team;
use App\Service\StatsService;
use App\Service\StatsViewService;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

class StatsViewServiceTest extends TestCase
{
    /**
     * Configure routing for URL assertions.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Router::reload();
        $routesConfig = require CONFIG . 'routes.php';
        $routesConfig(Router::createRouteBuilder('/'));
        Router::setRequest(new ServerRequest());
    }

    /**
     * Reset routing after the test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Router::reload();
        parent::tearDown();
    }

    /**
     * Ensure player season formatting returns table cells for a person row.
     *
     * @return void
     */
    public function testFormatPlayerSeasonRowsProducesCells(): void
    {
        $stub = new class extends StatsService {
            /**
             * Create a test stub without service dependencies.
             */
            public function __construct()
            {
            }

            /**
             * Return fixed player season stat cells.
             *
             * @param int $sportId The sport ID.
             * @param object $stat The player season stat record.
             * @return array<int,mixed>
             */
            public function getPlayerSeasonStatCells(int $sportId, object $stat): array
            {
                return [10, 20, 30];
            }

            /**
             * Return fixed team season stat cells.
             *
             * @param int $sportId The sport ID.
             * @param object $stat The team season stat record.
             * @return array<int,mixed>
             */
            public function getTeamSeasonStatCells(int $sportId, object $stat): array
            {
                return [40, 50];
            }
        };

        $viewService = new StatsViewService($stub);

        $person = (object)['id' => 5, 'display' => 'Jane Player', 'label' => 'J. Player'];
        $teamSeason = (object)['team' => (object)['team_name' => 'Lakers'], 'season' => (object)['start' => '2020', 'end' => '2021']];
        $team = new Team([
            'team_name' => 'Basketball',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);

        $rows = $viewService->playerSeasonRows([
            ['stat' => (object)['PTS' => 10], 'person' => $person, 'teamSeason' => $teamSeason],
            [
                'stat' => (object)['PTS' => 11],
                'person' => $person,
                'teamSeason' => (object)[
                    'team' => $team,
                    'season' => (object)['start' => '2021', 'end' => '2022'],
                ],
            ],
        ], 1);

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows[0]);
        $this->assertStringContainsString('Jane Player', (string)$rows[0][0]);
        $this->assertSame('Lakers', $rows[0][1]);
        $this->assertSame(h($team->getTeamDisplayName()), $rows[1][1]);

        $teamSeasonRows = $viewService->teamSeasonRows([
            [
                'stat' => (object)[],
                'teamSeason' => (object)[
                    'id' => 3,
                    'team' => $team,
                    'season' => (object)['start' => '2021', 'end' => '2022'],
                ],
            ],
        ], 1);

        $this->assertSame(h($team->getTeamDisplayName()), $teamSeasonRows[0][0]);
    }

    /**
     * Ensure team game row formatting handles opponent text variations.
     *
     * @return void
     */
    public function testFormatTeamGameRowsHandlesOpponentTextVariants(): void
    {
        $statsService = new class extends StatsService {
            /**
             * Create a test stub without service dependencies.
             */
            public function __construct()
            {
            }

            /**
             * Return fixed team game stat cells.
             *
             * @param int $sportId The sport ID.
             * @param object $stat The team game stat record.
             * @return array<int,mixed>
             */
            public function getTeamGameStatCells(int $sportId, object $stat): array
            {
                return [1, 2, 3];
            }
        };

        $viewService = new StatsViewService($statsService);

        $game = (object)[
            'id' => 10,
            'game_date' => '2025-01-01',
            'team_season' => (object)['team' => (object)['abbr' => 'ABC'], 'id' => 2],
            'opponent' => new Opponent([
                'opponent_name' => 'Opponent University',
                'opponent_short' => 'Opponent U',
                'opponent_mascot' => 'Rivals',
            ]),
        ];

        $rowsTeam = $viewService->teamGameRows([['stat' => (object)[], 'game' => $game]], 1, 'team-game');
        $this->assertIsArray($rowsTeam);
        $this->assertStringContainsString('ABC Vs Opponent U Rivals', $rowsTeam[0][0]);

        $rowsOpp = $viewService->teamGameRows([['stat' => (object)[], 'game' => $game]], 1, 'opponent-team-game');
        $this->assertIsArray($rowsOpp);
        $this->assertStringContainsString('Opponent U Rivals Vs ABC', $rowsOpp[0][0]);
    }
}
