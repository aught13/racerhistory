<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Entity\TeamSeason;

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

        $eav = $this->gameEavUi->mapLegacyKeys($this->gameService->loadGameEavValues($gameId));

        $viewData = [
            'game' => $game,
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
