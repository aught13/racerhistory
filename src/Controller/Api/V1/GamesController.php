<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Service\BasketballStatsService;
use App\Service\GameService;
use Cake\Datasource\Exception\RecordNotFoundException;

class GamesController extends AppController
{
    private GameService $gameService;
    private BasketballStatsService $basketballStatsService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->gameService = new GameService();
        $this->basketballStatsService = new BasketballStatsService();
    }

    /**
     * List games (supports ?q= and ?team_season_id= filters).
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $limit = $this->getLimit(50, 200);
        $q = trim((string)$this->getRequest()->getQuery('q', ''));
        $teamSeasonId = $this->getIntQuery('team_season_id');

        if ($q !== '' || $teamSeasonId !== null) {
            $results = $this->gameService->searchGamesForSelect($q, $teamSeasonId, $limit);
        } else {
            $results = $this->gameService->getRecentGamesForSelect($limit);
        }

        $this->respond([
            'data' => $results,
            'meta' => [
                'count' => count($results),
                'limit' => $limit,
                'q' => $q !== '' ? $q : null,
                'team_season_id' => $teamSeasonId,
            ],
        ]);
    }

    /**
     * Get a single game with related data.
     */
    public function view(int $id): void
    {
        $this->request->allowMethod(['get']);

        try {
            $game = $this->gameService->getGameWithAssociations($id);
        } catch (RecordNotFoundException) {
            $this->respondError('Game not found', 404);

            return;
        }

        $gameDate = $game->game_date;
        $gameDateString = null;
        if ($gameDate instanceof \Cake\I18n\Date) {
            $gameDateString = $gameDate->i18nFormat('yyyy-MM-dd');
        } elseif ($gameDate instanceof \DateTimeInterface) {
            $gameDateString = $gameDate->format('Y-m-d');
        } elseif ($gameDate !== null && $gameDate !== '') {
            $gameDateString = (string)$gameDate;
        }

        $payload = [
            'id' => (int)$game->id,
            'team_season_id' => $game->team_season_id !== null ? (int)$game->team_season_id : null,
            'game_date' => $gameDateString,
            'game_time' => $game->game_time ?? null,
            'hrn' => $game->hrn !== null ? (int)$game->hrn : null,
            'periods' => $game->get('periods') !== null ? (int)$game->get('periods') : null,
            'ot' => $game->get('ot') !== null ? (int)$game->get('ot') : null,
            'pts_mur' => $game->pts_mur !== null ? (int)$game->pts_mur : null,
            'pts_opp' => $game->pts_opp !== null ? (int)$game->pts_opp : null,
            'mur_rk' => $game->get('mur_rk') !== null ? (int)$game->get('mur_rk') : null,
            'opp_rk' => $game->get('opp_rk') !== null ? (int)$game->get('opp_rk') : null,
            'w' => $game->get('w') !== null ? (string)$game->get('w') : null,
            'l' => $game->get('l') !== null ? (string)$game->get('l') : null,
            'opponent' => [
                'id' => $game->opponent_id !== null ? (int)$game->opponent_id : null,
                'name' => $game->opponent->opponent_name ?? null,
            ],
            'game_type' => [
                'id' => $game->game_type_id !== null ? (int)$game->game_type_id : null,
                'name' => $game->game_type->game_type_name ?? null,
            ],
            'place' => [
                'id' => $game->place_id !== null ? (int)$game->place_id : null,
                'name' => $game->place->place_city ?? null,
                'state' => $game->place->place_state ?? null,
            ],
            'site' => [
                'id' => $game->site_id !== null ? (int)$game->site_id : null,
                'name' => $game->site->site_name ?? null,
            ],
            'team_season' => [
                'id' => $game->team_season_id !== null ? (int)$game->team_season_id : null,
                'team_name' => $game->team_season->team->team_name ?? null,
                'gender' => $game->team_season->team->gender ?? null,
                'sport' => $game->team_season->team->sport->sport_name ?? null,
                'season_start' => $game->team_season->season->start ?? null,
                'season_end' => $game->team_season->season->end ?? null,
            ],
            'eav' => $this->gameService->loadGameEavValues($id),
        ];

        $basketball = $this->basketballStatsService->getGameStats($id);
        if ($basketball !== null) {
            $payload['basketball_stats'] = $this->normalizeBasketballGameStats($basketball);
        }

        $this->respond(['data' => $payload]);
    }

    /**
     * @param array<string,mixed> $stats
     * @return array<string,mixed>
     */
    private function normalizeBasketballGameStats(array $stats): array
    {
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

        return [
            'teamBoxStats' => $stats['teamBoxStats'] ?? [],
            'opponentBoxStats' => $stats['opponentBoxStats'] ?? [],
            'teamPeriodStats' => $stats['teamPeriodStats'] ?? [],
            'opponentPeriodStats' => $stats['opponentPeriodStats'] ?? [],
            'playerStats' => $playerStats,
            'opponentPlayerStats' => $opponentPlayerStats,
            'teamTeamStats' => $teamTeamStats,
            'opponentTeamStats' => $opponentTeamStats,
            'hasPeriodStats' => (bool)($stats['hasPeriodStats'] ?? false),
        ];
    }
}
