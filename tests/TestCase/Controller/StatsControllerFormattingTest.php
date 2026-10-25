<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\StatsController;
use App\Model\Entity\Opponent;
use App\Model\Entity\Team;
use App\Service\StatsService;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Closure;

class StatsControllerFormattingTest extends TestCase
{
    /**
     * Ensure player season formatting returns table cells for a person row.
     *
     * @return void
     */
    public function testFormatPlayerSeasonRowsProducesCells(): void
    {
        $request = new ServerRequest();

        $controller = new class ($request) extends StatsController {
            /**
             * Initialize no-op for test stub.
             *
             * @return void
             */
            public function initialize(): void
            {
                // skip parent initialization to avoid loading components
            }

            /**
             * Test-friendly link generator that returns text only.
             *
             * @param string $text
             * @param array $url
             * @return string
             */
            protected function link(string $text, array $url): string
            {
                return (string)$text;
            }

            /**
             * Proxy to the protected formatPlayerSeasonRows for assertions.
             *
             * @param array $results
             * @param int $sportId
             * @return array
             */
            public function callFormatPlayerSeasonRows(array $results, int $sportId)
            {
                return $this->formatPlayerSeasonRows($results, $sportId);
            }

            /**
             * Proxy to the protected formatTeamSeasonRows for assertions.
             *
             * @param array $results
             * @param int $sportId
             * @return array
             */
            public function callFormatTeamSeasonRows(array $results, int $sportId)
            {
                return $this->formatTeamSeasonRows($results, $sportId);
            }
        };

        // stub statsService methods used by formatting; subclass StatsService so typing matches
        $stub = new class extends StatsService {
            /**
             * No-op constructor for typed stub.
             *
             * @return void
             */
            public function __construct()
            {
            }

            /**
             * Provide deterministic stat cell values for tests.
             *
             * @param int $sportId
             * @param object $stat
             * @return int[]
             */
            public function getPlayerSeasonStatCells(int $sportId, object $stat): array
            {
                return [10, 20, 30];
            }

            /**
             * Provide deterministic team season cells for assertions.
             *
             * @param int $sportId
             * @param object $stat
             * @return int[]
             */
            public function getTeamSeasonStatCells(int $sportId, object $stat): array
            {
                return [40, 50];
            }
        };
        $setter = Closure::bind(function ($svc) {
            $this->statsService = $svc;
        }, $controller, get_class($controller));
        $setter($stub);

        $person = (object)['id' => 5, 'display' => 'Jane Player', 'label' => 'J. Player'];
        $teamSeason = (object)['team' => (object)['team_name' => 'Lakers'], 'season' => (object)['start' => '2020', 'end' => '2021']];
        $team = new Team([
            'team_name' => 'Basketball',
            'team_nickname' => 'Racers',
            'gender' => Team::GENDER_MALE,
        ]);

        $rows = $controller->callFormatPlayerSeasonRows([
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
        $this->assertSame(h($team->getDisplayName()), $rows[1][1]);

        $teamSeasonRows = $controller->callFormatTeamSeasonRows([
            [
                'stat' => (object)[],
                'teamSeason' => (object)[
                    'id' => 3,
                    'team' => $team,
                    'season' => (object)['start' => '2021', 'end' => '2022'],
                ],
            ],
        ], 1);

        $this->assertSame(h($team->getDisplayName()), $teamSeasonRows[0][0]);
    }

    /**
     * Ensure team game row formatting handles opponent text variations.
     *
     * @return void
     */
    public function testFormatTeamGameRowsHandlesOpponentTextVariants(): void
    {
        $request = new ServerRequest();

        $controller = new class ($request) extends StatsController {
            /**
             * Initialize no-op for test stub.
             *
             * @return void
             */
            public function initialize(): void
            {
                // skip parent initialization
            }

            /**
             * Test-friendly link generator that returns text only.
             *
             * @param string $text
             * @param array $url
             * @return string
             */
            protected function link(string $text, array $url): string
            {
                return (string)$text;
            }

            /**
             * Proxy to the protected formatTeamGameRows for assertions.
             *
             * @param array $results
             * @param int $sportId
             * @param string $statType
             * @return array
             */
            public function callFormatTeamGameRows(array $results, int $sportId, string $statType)
            {
                return $this->formatTeamGameRows($results, $sportId, $statType);
            }
        };

        $stub2 = new class extends StatsService {
            /**
             * No-op constructor for typed stub.
             *
             * @return void
             */
            public function __construct()
            {
            }

            /**
             * Provide deterministic team game stat cells for tests.
             *
             * @param int $sportId
             * @param object $stat
             * @return int[]
             */
            public function getTeamGameStatCells(int $sportId, object $stat): array
            {
                return [1, 2, 3];
            }
        };
        $setter2 = Closure::bind(function ($svc) {
            $this->statsService = $svc;
        }, $controller, get_class($controller));
        $setter2($stub2);

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

        $rowsTeam = $controller->callFormatTeamGameRows([['stat' => (object)[], 'game' => $game]], 1, 'team-game');
        $this->assertIsArray($rowsTeam);
        $this->assertSame('ABC Vs Opponent U Rivals', $rowsTeam[0][0]);

        $rowsOpp = $controller->callFormatTeamGameRows([['stat' => (object)[], 'game' => $game]], 1, 'opponent-team-game');
        $this->assertIsArray($rowsOpp);
        $this->assertSame('Opponent U Rivals Vs ABC', $rowsOpp[0][0]);
    }
}
