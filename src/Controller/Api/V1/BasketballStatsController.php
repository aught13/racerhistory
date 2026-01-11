<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\BasketballStatsService;

class BasketballStatsController extends AppController
{
    private BasketballStatsService $basketballStatsService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->basketballStatsService = new BasketballStatsService();
    }

    /**
     * Get basketball stats for a game.
     */
    public function game(int $gameId): void
    {
        $this->request->allowMethod(['get']);

        $stats = $this->basketballStatsService->getGameStats($gameId);
        if ($stats === null) {
            $this->respondError('Basketball game stats not found', 404);

            return;
        }

        $playerStats = [];
        foreach (($stats['playerStats'] ?? []) as $row) {
            $playerStats[] = $row->toArray();
        }

        $opponentPlayerStats = [];
        foreach (($stats['opponentPlayerStats'] ?? []) as $row) {
            $opponentPlayerStats[] = $row->toArray();
        }

        $teamTeamStats = null;
        if (isset($stats['teamTeamStats']) && $stats['teamTeamStats']) {
            $teamTeamStats = $stats['teamTeamStats']->toArray();
        }

        $opponentTeamStats = null;
        if (isset($stats['opponentTeamStats']) && $stats['opponentTeamStats']) {
            $opponentTeamStats = $stats['opponentTeamStats']->toArray();
        }

        $this->respond([
            'data' => [
                'teamBoxStats' => $stats['teamBoxStats'] ?? [],
                'opponentBoxStats' => $stats['opponentBoxStats'] ?? [],
                'teamPeriodStats' => $stats['teamPeriodStats'] ?? [],
                'opponentPeriodStats' => $stats['opponentPeriodStats'] ?? [],
                'playerStats' => $playerStats,
                'opponentPlayerStats' => $opponentPlayerStats,
                'teamTeamStats' => $teamTeamStats,
                'opponentTeamStats' => $opponentTeamStats,
                'hasPeriodStats' => (bool)($stats['hasPeriodStats'] ?? false),
            ],
        ]);
    }

    /**
     * Get basketball season totals for a team season.
     */
    public function season(int $teamSeasonId): void
    {
        $this->request->allowMethod(['get']);

        $stats = $this->basketballStatsService->getSeasonStats($teamSeasonId);
        if ($stats === null) {
            $this->respondError('Basketball season stats not found', 404);

            return;
        }

        $playerStats = [];
        foreach (($stats['playerStats'] ?? []) as $row) {
            $playerStats[] = $row->toArray();
        }

        $teamStats = null;
        if (isset($stats['teamStats']) && $stats['teamStats']) {
            $teamStats = $stats['teamStats']->toArray();
        }

        $opponentStats = null;
        if (isset($stats['opponentStats']) && $stats['opponentStats']) {
            $opponentStats = $stats['opponentStats']->toArray();
        }

        $this->respond([
            'data' => [
                'playerStats' => $playerStats,
                'teamStats' => $teamStats,
                'opponentStats' => $opponentStats,
            ],
        ]);
    }
}
