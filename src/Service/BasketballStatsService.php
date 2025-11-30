<?php
declare(strict_types=1);

namespace App\Service;

use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
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
    use ServiceAwareTrait;

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

        // Load box score stats if available using flexible final markers
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        $finalMarkers = ['Z', 'F', 'FINAL'];

        // Load team final stats (any recognized final marker, opponent_id 0)
        $teamBox = $boxTable->find()
            ->where([
                'game_id' => $gameId,
                'opponent_id' => 0,
                'period IN' => $finalMarkers,
            ])
            ->first();
        if (!$teamBox) {
            // Fallback: if data was stored without opponent_id = 0, attempt with actual opponent id
            $teamBox = $boxTable->find()
                ->where([
                    'game_id' => $gameId,
                    'opponent_id' => $game->opponent_id ?? 0,
                    'period IN' => $finalMarkers,
                ])
                ->first();
        }
        if ($teamBox) {
            $teamBoxStats = $teamBox->toArray();
        }

        // Load opponent final stats (recognized final marker, opponent id)
        $opponentId = $game->opponent_id ?? 0;
        $opponentBox = $boxTable->find()
            ->where([
                'game_id' => $gameId,
                'opponent_id' => $opponentId,
                'period IN' => $finalMarkers,
            ])
            ->first();
        if ($opponentBox) {
            $opponentBoxStats = $opponentBox->toArray();
        }

        // Load period stats excluding finals
        $periodStatsData = $boxTable->find()
            ->where([
                'game_id' => $gameId,
                'period NOT IN' => $finalMarkers,
            ])
            ->orderBy(['period' => 'ASC'])
            ->all();

        foreach ($periodStatsData as $periodStat) {
            if ($periodStat->opponent_id == 0) {
                $teamPeriodStats[$periodStat->period] = $periodStat->toArray();
            } elseif ($periodStat->opponent_id == $opponentId) {
                $opponentPeriodStats[$periodStat->period] = $periodStat->toArray();
            }
        }

        $hasPeriodStats = $periodStatsData->count() > 0;

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
            ->where(['StatBasketGameOpponent.game_id' => $gameId])
            ->orderBy(['StatBasketGameOpponent.name' => 'ASC'])
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

    /**
     * Get basketball season statistics for display in team season view
     *
     * Loads all basketball-specific statistics for a team season.
     *
     * @param int $teamSeasonId Team Season ID
     * @return array|null Array with keys: playerStats, teamStats, opponentStats
     *                     Returns null if not a basketball season
     */
    public function getSeasonStats(int $teamSeasonId): ?array
    {
        /** @var \App\Model\Table\TeamSeasonsTable $teamSeasonsTable */
        $teamSeasonsTable = $this->fetchTable('TeamSeasons');

        $teamSeason = $teamSeasonsTable->find()
            ->contain(['Teams' => ['Sports']])
            ->where(['TeamSeasons.id' => $teamSeasonId])
            ->first();

        if (!$teamSeason || !$teamSeason->team || !$teamSeason->team->sport) {
            return null;
        }

        $sportName = strtolower($teamSeason->team->sport->sport_name);
        if ($sportName !== 'basketball') {
            return null;
        }

        // Load player stats
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $personTable */
        $personTable = $this->fetchTable('StatBasketSeasonPerson');
        $playerStats = $personTable->find()
            ->contain(['TeamSeasonRosters' => ['Persons']])
            ->where(['TeamSeasonRosters.team_season_id' => $teamSeasonId])
            ->all();

        // Load team stats
        /** @var \App\Model\Table\StatBasketSeasonTeamTable $teamTable */
        $teamTable = $this->fetchTable('StatBasketSeasonTeam');
        $teamStats = $teamTable->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        // Load opponent stats
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $opponentTable */
        $opponentTable = $this->fetchTable('StatBasketSeasonOpponent');
        $opponentStats = $opponentTable->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        return compact('playerStats', 'teamStats', 'opponentStats');
    }

    /**
     * Initialize basketball stats array with zero values
     *
     * @param string $type Stat type ('player', 'team', 'opponent')
     * @return array<string, int> Zeroed stats array
     */
    public function initializeStats(string $type = 'player'): array
    {
        // Standard player stat fields
        if ($type === 'player') {
            return [
                'GP' => 0, 'GS' => 0, 'MIN' => 0, 'FGM' => 0, 'FGA' => 0,
                'TPM' => 0, 'TPA' => 0, 'FTM' => 0, 'FTA' => 0,
                'ORB' => 0, 'DRB' => 0, 'RB' => 0, 'AST' => 0, 'STL' => 0,
                'BS' => 0, 'TRN' => 0, 'PF' => 0, 'TF' => 0, 'PTS' => 0,
            ];
        }

        // Team/opponent stats would be similar but might have additional fields
        return [];
    }

    /**
     * Add season stats to career totals
     *
     * Sums all numeric basketball stat fields from season stats into career totals.
     *
     * @param array<string, int> $totals Career totals array (modified by reference)
     * @param \App\Model\Entity\StatBasketSeasonPerson $seasonStats Season stats entity
     * @return void
     */
    public function addSeasonStats(array &$totals, \App\Model\Entity\StatBasketSeasonPerson $seasonStats): void
    {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
                   'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF', 'PTS'];

        foreach ($fields as $field) {
            $value = $seasonStats->$field ?? 0;
            $totals[$field] += is_numeric($value) ? (int)$value : 0;
        }
    }

    /**
     * Get a person's basketball season statistics by team season roster id
     *
     * @param int $teamSeasonRosterId Team season roster ID
     * @return \App\Model\Entity\StatBasketSeasonPerson|null
     */
    public function getPersonSeasonStats(int $teamSeasonRosterId): ?\App\Model\Entity\StatBasketSeasonPerson
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $personTable */
        $personTable = $this->fetchTable('StatBasketSeasonPerson');

        /** @var \App\Model\Entity\StatBasketSeasonPerson|null $row */
        $row = $personTable->find()
            ->where(['team_season_roster_id' => $teamSeasonRosterId])
            ->first();

        return $row;
    }

    /**
     * Get a person's basketball game statistics grouped by game
     *
     * Returns an array of entries with 'game' (Game entity) and 'stats' (array of StatBasketGamePerson rows)
     * similar to the structure expected by the Persons view template.
     *
     * @param int $teamSeasonRosterId Team season roster ID
     * @return array<int, array{game: object, stats: array<int, object>}>
     */
    public function getPersonGameStats(int $teamSeasonRosterId): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $gpTable */
        $gpTable = $this->fetchTable('StatBasketGamePerson');

        $rows = $gpTable->find()
            ->contain(['Games' => ['Opponents']])
            ->where(['StatBasketGamePerson.team_season_roster_id' => $teamSeasonRosterId])
            ->orderBy(['StatBasketGamePerson.game_id' => 'ASC', 'StatBasketGamePerson.period' => 'ASC'])
            ->all();

        $grouped = [];
        foreach ($rows as $row) {
            $gameId = (int)$row->game_id;
            if (!isset($grouped[$gameId])) {
                $grouped[$gameId] = [
                    'game' => $row->game,
                    'stats' => [],
                ];
            }
            $grouped[$gameId]['stats'][] = $row;
        }

        return array_values($grouped);
    }
}
