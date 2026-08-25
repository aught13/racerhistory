<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Entity\TeamSeason;
use Cake\ORM\TableRegistry;

/**
 * GameViewService
 *
 * Prepares view-model data for the Admin/Games view page.
 */
class GameViewService
{
    private GameService $gameService;
    private SportConfigService $sportConfigService;
    private StatsService $statsService;
    private GameEavUiService $gameEavUi;
    private TeamSportContextService $teamSportContextService;

    /**
     * Constructor.
     *
     * @param \App\Service\GameService|null $gameService Game service
     * @param \App\Service\SportConfigService|null $sportConfigService Sport config service
     * @param \App\Service\StatsService|null $statsService Stats service
     * @param \App\Service\GameEavUiService|null $gameEavUi EAV UI helper
     */
    public function __construct(
        ?GameService $gameService = null,
        ?SportConfigService $sportConfigService = null,
        ?StatsService $statsService = null,
        ?GameEavUiService $gameEavUi = null,
    ) {
        $this->gameService = $gameService ?? new GameService();
        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
        $this->statsService = $statsService ?? new StatsService($this->sportConfigService);
        $this->gameEavUi = $gameEavUi ?? new GameEavUiService();
        $this->teamSportContextService = new TeamSportContextService($this->sportConfigService);
    }

    /**
     * Build variables for the admin view page.
     *
     * @param int $gameId Game ID
     * @return array<string,mixed>
     */
    public function getViewData(int $gameId): array
    {
        /** @var \App\Model\Entity\Game $game */
        $game = $this->gameService->getGameWithAssociations($gameId);

        $previousGame = $this->resolveAdjacentGame($game, 'previous');
        $nextGame = $this->resolveAdjacentGame($game, 'next');

        $eav = $this->gameEavUi->mapLegacyKeys($this->gameService->loadGameEavValues($gameId));

        $viewData = [
            'game' => $game,
            'previousGame' => $previousGame,
            'nextGame' => $nextGame,
            'eav' => $eav,

            'teamBoxStats' => [],
            'opponentBoxStats' => [],
            'teamPeriodStats' => [],
            'opponentPeriodStats' => [],
            'playerStats' => [],
            'opponentPlayerStats' => [],
            'teamTeamStats' => null,
            'opponentTeamStats' => null,

            'hasSportConfig' => false,
            'hasPeriodStats' => false,
            'fieldLabels' => [],
        ];

        $sportId = $this->resolveSportId($game);
        $teamSeason = $game->get('team_season');
        $team = $teamSeason instanceof TeamSeason ? $teamSeason->get('team') : null;
        $sportName = $this->teamSportContextService->resolveSportNameFromTeam($team);
        if ($sportId) {
            $viewData['hasSportConfig'] = true;

            $sportStats = $this->statsService->getGameStats($gameId);
            if ($sportStats) {
                $viewData['teamBoxStats'] = $sportStats['teamBoxStats'] ?? [];
                $viewData['opponentBoxStats'] = $sportStats['opponentBoxStats'] ?? [];
                $viewData['teamPeriodStats'] = $sportStats['teamPeriodStats'] ?? [];
                $viewData['opponentPeriodStats'] = $sportStats['opponentPeriodStats'] ?? [];
                $viewData['playerStats'] = $sportStats['playerStats'] ?? [];
                $viewData['opponentPlayerStats'] = $sportStats['opponentPlayerStats'] ?? [];
                $viewData['teamTeamStats'] = $sportStats['teamTeamStats'] ?? null;
                $viewData['opponentTeamStats'] = $sportStats['opponentTeamStats'] ?? null;
                $viewData['hasPeriodStats'] = (bool)($sportStats['hasPeriodStats'] ?? false);
            }

            $viewData['fieldLabels'] = $this->sportConfigService->getAllFieldLabels((int)$sportId);
        }

        $viewData['sportName'] = $sportName;

        return $viewData;
    }

    /**
     * @param \App\Model\Entity\Game $game
     * @param 'previous'|'next' $direction
     * @return \App\Model\Entity\Game|null
     */
    private function resolveAdjacentGame(object $game, string $direction): ?object
    {
        $teamSeason = $game->get('team_season');
        $teamSeasonId = $game->team_season_id ?? ($teamSeason instanceof TeamSeason ? $teamSeason->id : null);
        if ($teamSeasonId === null || $game->game_date === null) {
            return null;
        }

        $gamesTable = TableRegistry::getTableLocator()->get('Games');
        $query = $gamesTable->find()
            ->where(['Games.team_season_id' => $teamSeasonId])
            ->where(['Games.id !=' => $game->id]);

        if ($direction === 'previous') {
            $query->where(['Games.game_date <' => $game->game_date])
                ->orderByDesc('Games.game_date');
        } else {
            $query->where(['Games.game_date >' => $game->game_date])
                ->orderByAsc('Games.game_date');
        }

        $adjacent = $query->first();

        return $adjacent instanceof Game ? $adjacent : null;
    }

    /**
     * Resolve sport id from optional nested associations.
     *
     * @param \App\Model\Entity\Game $game Game entity with optional associations.
     * @return int|null
     */
    private function resolveSportId(Game $game): ?int
    {
        $teamSeason = $game->get('team_season');
        if (!$teamSeason instanceof TeamSeason) {
            return null;
        }

        $team = $teamSeason->get('team');
        if ($team === null) {
            return null;
        }

        $this->teamSportContextService->attachSportContextToTeam($team);

        return $this->teamSportContextService->resolveSportIdFromTeam($team);
    }
}
