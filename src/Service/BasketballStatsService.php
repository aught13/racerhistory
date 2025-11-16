<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * BasketballStatsService
 *
 * Service for loading and managing basketball-specific statistics.
 * This centralizes basketball stats loading logic for use across multiple controllers.
 */
class BasketballStatsService
{
    use LocatorAwareTrait;
    /**
     * Get basketball game statistics for display in game view
     *
     * Loads all basketball-specific statistics (box scores, player stats, etc.)
     * for display in the game view template. This centralizes all sport-specific
     * data loading outside of the GamesController.
     *
     * @param int $gameId Game ID
     * @return array|null Array with keys: teamBoxStats, opponentBoxStats, teamPeriodStats,
     *                     opponentPeriodStats, playerStats, opponentPlayerStats,
     *                     teamTeamStats, opponentTeamStats, hasPeriodStats
     *                     Returns null if not a basketball game
     */
    public function getGameStats(int $gameId): ?array
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->find()
            ->contain([
                'TeamSeason' => ['Teams' => ['Sports'], 'Seasons'],
                'GameTypes',
                'Opponents',
                'Sites' => ['Places'],
                'Places',
            ])
            ->where(['Games.id' => $gameId])
            ->first();

        if (!$game || !$game->team_season || !$game->team_season->team || !$game->team_season->team->sport) {
            return null;
        }

        $sportName = strtolower($game->team_season->team->sport->sport_name);
        if ($sportName !== 'basketball') {
            return null;
        }

        // Initialize stat variables
        $teamBoxStats = [];
        $opponentBoxStats = [];
        $teamPeriodStats = [];
        $opponentPeriodStats = [];
        $playerStats = [];
        $opponentPlayerStats = [];
        $teamTeamStats = null;
        $opponentTeamStats = null;
        $hasPeriodStats = false;

        // Load box score stats if available
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');

        // Load team final stats (period Z, opponent_id 0)
        $teamBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'])
            ->first();

        if ($teamBox) {
            $teamBoxStats = $teamBox->toArray();
        }

        // Load opponent final stats (period Z, with opponent_id)
        $opponentId = $game->opponent_id ?? 0;
        $opponentBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'])
            ->first();

        if ($opponentBox) {
            $opponentBoxStats = $opponentBox->toArray();
        }

        // Load period stats for both teams (for half-by-half breakdowns)
        $periodStatsData = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->order(['period' => 'ASC'])
            ->all();

        foreach ($periodStatsData as $periodStat) {
            if ($periodStat->opponent_id == 0) {
                $teamPeriodStats[$periodStat->period] = $periodStat->toArray();
            } elseif ($periodStat->opponent_id == $opponentId) {
                $opponentPeriodStats[$periodStat->period] = $periodStat->toArray();
            }
        }

        $hasPeriodStats = !empty($periodStatsData);

        // Load player stats (period Z final stats)
        /** @var \App\Model\Table\StatBasketGamePersonTable $personTable */
        $personTable = $this->fetchTable('StatBasketGamePerson');
        $playerStats = $personTable->find()
            ->contain(['TeamSeasonRosters' => ['Persons', 'TeamSeasons']])
            ->where(['StatBasketGamePerson.game_id' => $gameId, 'StatBasketGamePerson.period' => 'Z'])
            ->orderBy(function ($exp, $query) {
                return [
                    $query->newExpr('COALESCE(StatBasketGamePerson.GS, 0) DESC'),
                    $query->newExpr('COALESCE(StatBasketGamePerson.MIN, 0) DESC'),
                    'StatBasketGamePerson.PTS' => 'DESC',
                ];
            })
            ->all();

        // Load opponent player stats (period Z final stats)
        /** @var \App\Model\Table\StatBasketGameOpponentTable $opponentTable */
        $opponentTable = $this->fetchTable('StatBasketGameOpponent');
        $opponentPlayerStats = $opponentTable->find()
            ->where(['StatBasketGameOpponent.game_id' => $gameId, 'StatBasketGameOpponent.period' => 'Z'])
            ->orderBy(function ($exp, $query) {
                return [
                    $query->newExpr('COALESCE(StatBasketGameOpponent.GS, 0) DESC'),
                    $query->newExpr('COALESCE(StatBasketGameOpponent.MIN, 0) DESC'),
                    'StatBasketGameOpponent.PTS' => 'DESC',
                ];
            })
            ->all();

        // Load team stats (Dead Ball rebounds, Fouls Drawn, Team Turnovers) for period Z
        /** @var \App\Model\Table\StatBasketGameTeamTable $teamTable */
        $teamTable = $this->fetchTable('StatBasketGameTeam');

        $teamTeamStats = $teamTable->find()
            ->where(['StatBasketGameTeam.game_id' => $gameId, 'StatBasketGameTeam.opp' => 0])
            ->first();

        $opponentTeamStats = $teamTable->find()
            ->where(['StatBasketGameTeam.game_id' => $gameId, 'StatBasketGameTeam.opp' => 1])
            ->first();

        return compact(
            'teamBoxStats',
            'opponentBoxStats',
            'teamPeriodStats',
            'opponentPeriodStats',
            'playerStats',
            'opponentPlayerStats',
            'teamTeamStats',
            'opponentTeamStats',
            'hasPeriodStats'
        );
    }
}
