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
     * Determine which season stats columns have data and should be displayed.
     *
     * @param \Cake\Collection\CollectionInterface|array|null $playerStats Player stat rows
     * @param object|null $teamStats Team totals
     * @param object|null $opponentStats Opponent totals
     * @return array<string, string>
     */
    public function getSeasonStatColumns(
        \Cake\Collection\CollectionInterface|array|null $playerStats,
        ?object $teamStats,
        ?object $opponentStats,
    ): array {
        $allColumns = [
            'GP' => 'GP',
            'GS' => 'GS',
            'MIN' => 'MIN',
            'FGM' => 'FGM',
            'FGA' => 'FGA',
            'TPM' => '3PM',
            'TPA' => '3PA',
            'FTM' => 'FTM',
            'FTA' => 'FTA',
            'ORB' => 'ORB',
            'DRB' => 'DRB',
            'RB' => 'RB',
            'AST' => 'AST',
            'STL' => 'STL',
            'BS' => 'BS',
            'TRN' => 'TRN',
            'PF' => 'PF',
            'PTS' => 'PTS',
        ];

        $visible = [];
        foreach ($allColumns as $key => $label) {
            if ($this->columnHasData($key, $playerStats, $teamStats, $opponentStats)) {
                $visible[$key] = $label;
            }
        }

        return $visible;
    }

    /**
     * Determine whether a column has visible data.
     *
     * @param string $key Column key
     * @param \Cake\Collection\CollectionInterface|array|null $playerStats Player stats
     * @param object|null $teamStats Team totals
     * @param object|null $opponentStats Opponent totals
     * @return bool
     */
    protected function columnHasData(
        string $key,
        \Cake\Collection\CollectionInterface|array|null $playerStats,
        ?object $teamStats,
        ?object $opponentStats,
    ): bool {
        if ($playerStats) {
            foreach ($playerStats as $stat) {
                if (!empty($stat->$key)) {
                    return true;
                }
            }
        }

        if ($teamStats && !empty($teamStats->$key)) {
            return true;
        }

        if ($opponentStats && !empty($opponentStats->$key)) {
            return true;
        }

        return false;
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
                'GP' => 0,
                'GS' => 0,
                'MIN' => 0,
                'FGM' => 0,
                'FGA' => 0,
                'TPM' => 0,
                'TPA' => 0,
                'FTM' => 0,
                'FTA' => 0,
                'ORB' => 0,
                'DRB' => 0,
                'RB' => 0,
                'AST' => 0,
                'STL' => 0,
                'BS' => 0,
                'TRN' => 0,
                'PF' => 0,
                'TF' => 0,
                'PTS' => 0,
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

    /**
     * Add a single player's final (period Z) game stat into their season totals.
     *
     * @param \App\Model\Entity\StatBasketGamePerson $gameStat
     * @return bool
     */
    public function addGamePersonStatToSeasonTotals(\App\Model\Entity\StatBasketGamePerson $gameStat): bool
    {
        if (!$gameStat->team_season_roster_id || (string)$gameStat->period !== 'Z') {
            return false;
        }

        /** @var \App\Model\Table\StatBasketSeasonPersonTable $seasonTable */
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        /** @var \App\Model\Entity\StatBasketSeasonPerson|null $seasonStat */
        $seasonStat = $seasonTable->find()
            ->where(['team_season_roster_id' => $gameStat->team_season_roster_id])
            ->first();

        if (!$seasonStat) {
            $seasonStat = $seasonTable->newEmptyEntity();
            $seasonStat->team_season_roster_id = $gameStat->team_season_roster_id;
        }

        $this->addSeasonPersonStatValues($seasonStat, $gameStat);

        return (bool)$seasonTable->save($seasonStat);
    }

    /**
     * Update season totals when a player's final game stat is edited.
     *
     * @param \App\Model\Entity\StatBasketGamePerson $original
     * @param \App\Model\Entity\StatBasketGamePerson $updated
     * @return bool
     */
    public function updateGamePersonStatSeasonTotals(
        \App\Model\Entity\StatBasketGamePerson $original,
        \App\Model\Entity\StatBasketGamePerson $updated,
    ): bool {
        if (!$updated->team_season_roster_id || (string)$updated->period !== 'Z') {
            return false;
        }

        /** @var \App\Model\Table\StatBasketSeasonPersonTable $seasonTable */
        $seasonTable = $this->fetchTable('StatBasketSeasonPerson');

        /** @var \App\Model\Entity\StatBasketSeasonPerson|null $seasonStat */
        $seasonStat = $seasonTable->find()
            ->where(['team_season_roster_id' => $updated->team_season_roster_id])
            ->first();

        if (!$seasonStat) {
            return $this->addGamePersonStatToSeasonTotals($updated);
        }

        $this->subtractSeasonPersonStatValues($seasonStat, $original);
        $this->addSeasonPersonStatValues($seasonStat, $updated);

        return (bool)$seasonTable->save($seasonStat);
    }

    /**
     * Apply basketball team/opponent final box score (period Z) into season totals.
     *
     * @param \App\Model\Entity\Game $game
     * @param \App\Model\Entity\StatBasketGameBox $teamBox
     * @param \App\Model\Entity\StatBasketGameBox $opponentBox
     * @param \App\Model\Entity\StatBasketGameBox|null $originalTeamBox
     * @param \App\Model\Entity\StatBasketGameBox|null $originalOpponentBox
     * @return bool
     */
    public function applyGameBoxToSeasonTotals(
        \App\Model\Entity\Game $game,
        \App\Model\Entity\StatBasketGameBox $teamBox,
        \App\Model\Entity\StatBasketGameBox $opponentBox,
        ?\App\Model\Entity\StatBasketGameBox $originalTeamBox = null,
        ?\App\Model\Entity\StatBasketGameBox $originalOpponentBox = null,
    ): bool {
        if (!$game->team_season_id) {
            return false;
        }

        /** @var \App\Model\Table\StatBasketSeasonTeamTable $teamSeasonTable */
        $teamSeasonTable = $this->fetchTable('StatBasketSeasonTeam');
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $opponentSeasonTable */
        $opponentSeasonTable = $this->fetchTable('StatBasketSeasonOpponent');

        $sumFields = [
            'GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS',
            'TF', 'FD', 'BD', 'EB', 'PP', 'FB', 'BN', 'TIED', 'LC',
        ];

        $teamSeasonStat = $teamSeasonTable->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$teamSeasonStat) {
            $teamSeasonStat = $teamSeasonTable->newEmptyEntity();
            $teamSeasonStat->team_season_id = $game->team_season_id;
        }

        if ($originalTeamBox) {
            foreach ($sumFields as $field) {
                $current = (int)($teamSeasonStat->get($field) ?? 0);
                $originalValue = (int)($originalTeamBox->get($field) ?? 0);
                if ($originalValue !== 0) {
                    $teamSeasonStat->set($field, max(0, $current - $originalValue));
                }
            }
        }
        foreach ($sumFields as $field) {
            $newValue = (int)($teamBox->get($field) ?? 0);
            if ($newValue !== 0) {
                $current = (int)($teamSeasonStat->get($field) ?? 0);
                $teamSeasonStat->set($field, $current + $newValue);
            }
        }

        $okTeam = (bool)$teamSeasonTable->save($teamSeasonStat);

        $opponentSeasonStat = $opponentSeasonTable->find()
            ->where(['team_season_id' => $game->team_season_id])
            ->first();

        if (!$opponentSeasonStat) {
            $opponentSeasonStat = $opponentSeasonTable->newEmptyEntity();
            $opponentSeasonStat->team_season_id = $game->team_season_id;
        }

        if ($originalOpponentBox) {
            foreach ($sumFields as $field) {
                $current = (int)($opponentSeasonStat->get($field) ?? 0);
                $originalValue = (int)($originalOpponentBox->get($field) ?? 0);
                if ($originalValue !== 0) {
                    $opponentSeasonStat->set($field, max(0, $current - $originalValue));
                }
            }
        }
        foreach ($sumFields as $field) {
            $newValue = (int)($opponentBox->get($field) ?? 0);
            if ($newValue !== 0) {
                $current = (int)($opponentSeasonStat->get($field) ?? 0);
                $opponentSeasonStat->set($field, $current + $newValue);
            }
        }

        $okOpponent = (bool)$opponentSeasonTable->save($opponentSeasonStat);

        return $okTeam && $okOpponent;
    }

    /**
     * Search player season stats with filters.
     *
     * @param array $filters Optional filters: season_id, team_id, sort, direction, limit
     * @return array<int, array{stat: object, person: object, teamSeason: object}>
     */
    public function searchPlayerSeasonStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');

        $query = $table->find()
            ->contain([
                'TeamSeasonRosters' => [
                    'Persons',
                    'TeamSeasons' => ['Teams', 'Seasons'],
                ],
            ]);

        if (!empty($filters['season_id'])) {
            $query->where(['TeamSeasons.season_id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['TeamSeasons.team_id' => (int)$filters['team_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketSeasonPerson.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $roster = $stat->team_season_roster ?? null;
            if (!$roster) {
                continue;
            }
            $results[] = [
                'stat' => $stat,
                'person' => $roster->person ?? null,
                'teamSeason' => $roster->team_season ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search team season stats with filters.
     *
     * @param array $filters Optional filters: season_id, team_id, sort, direction, limit
     * @return array<int, array{stat: object, teamSeason: object}>
     */
    public function searchTeamSeasonStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketSeasonTeamTable $table */
        $table = $this->fetchTable('StatBasketSeasonTeam');

        $query = $table->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']]);

        if (!empty($filters['season_id'])) {
            $query->where(['TeamSeasons.season_id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['TeamSeasons.team_id' => (int)$filters['team_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['GP', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketSeasonTeam.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $results[] = [
                'stat' => $stat,
                'teamSeason' => $stat->team_season ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search team season opponent stats with filters.
     *
     * @param array $filters Optional filters: season_id, team_id, sort, direction, limit
     * @return array<int, array{stat: object, teamSeason: object}>
     */
    public function searchTeamSeasonOpponentStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $table */
        $table = $this->fetchTable('StatBasketSeasonOpponent');

        $query = $table->find()
            ->contain(['TeamSeasons' => ['Teams', 'Seasons']]);

        if (!empty($filters['season_id'])) {
            $query->where(['TeamSeasons.season_id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['TeamSeasons.team_id' => (int)$filters['team_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['GP', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketSeasonOpponent.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $results[] = [
                'stat' => $stat,
                'teamSeason' => $stat->team_season ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search player game stats with filters.
     *
     * @param array $filters Optional filters: season_id, team_id, game_id, sort, direction, limit
     * @return array<int, array{stat: object, person: object, game: object}>
     */
    public function searchPlayerGameStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');

        $query = $table->find()
            ->contain([
                'TeamSeasonRosters' => [
                    'Persons',
                    'TeamSeasons' => ['Teams', 'Seasons'],
                ],
                'Games' => ['Opponents'],
            ])
            ->where(['StatBasketGamePerson.period' => 'Z']);

        if (!empty($filters['season_id'])) {
            $query->where(['TeamSeasons.season_id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['TeamSeasons.team_id' => (int)$filters['team_id']]);
        }
        if (!empty($filters['game_id'])) {
            $query->where(['StatBasketGamePerson.game_id' => (int)$filters['game_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketGamePerson.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $roster = $stat->team_season_roster ?? null;
            $results[] = [
                'stat' => $stat,
                'person' => $roster->person ?? null,
                'game' => $stat->game ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search opponent player game stats with filters.
     *
     * @param array $filters Optional filters: season_id, team_id, game_id, sort, direction, limit
     * @return array<int, array{stat: object, game: object}>
     */
    public function searchOpponentPlayerGameStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');

        $query = $table->find()
            ->contain(['Games' => ['Opponents', 'TeamSeason' => ['Teams', 'Seasons']]]);

        if (!empty($filters['season_id'])) {
            $query->where(['Seasons.id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['Teams.id' => (int)$filters['team_id']]);
        }
        if (!empty($filters['game_id'])) {
            $query->where(['StatBasketGameOpponent.game_id' => (int)$filters['game_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketGameOpponent.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $results[] = [
                'stat' => $stat,
                'game' => $stat->game ?? null,
            ];
        }

        return $results;
    }

    /**
     * Search team game box score stats (final totals per game).
     *
     * Returns the team's final box score rows (period in Z/F/FINAL, opponent_id = 0)
     * along with game and opponent info.
     *
     * @param array $filters Optional filters: season_id, team_id, game_id, sort, direction, limit
     * @return array<int, array{stat: object, game: object}>
     */
    public function searchTeamGameStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketGameBoxTable $table */
        $table = $this->fetchTable('StatBasketGameBox');

        $query = $table->find()
            ->contain(['Games' => ['Opponents', 'TeamSeason' => ['Teams', 'Seasons']]])
            ->where([
                'StatBasketGameBox.opponent_id' => 0,
                'StatBasketGameBox.period IN' => ['Z', 'F', 'FINAL'],
            ]);

        if (!empty($filters['season_id'])) {
            $query->where(['Seasons.id' => (int)$filters['season_id']]);
        }
        if (!empty($filters['team_id'])) {
            $query->where(['Teams.id' => (int)$filters['team_id']]);
        }
        if (!empty($filters['game_id'])) {
            $query->where(['StatBasketGameBox.game_id' => (int)$filters['game_id']]);
        }

        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = ['FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'PTS'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }
        $query->orderBy(["StatBasketGameBox.{$sort}" => $direction]);

        $limit = (int)($filters['limit'] ?? 50);
        if ($limit > 0) {
            $query->limit(min($limit, 5000));
        }

        $results = [];
        foreach ($query->all() as $stat) {
            $results[] = [
                'stat' => $stat,
                'game' => $stat->game ?? null,
            ];
        }

        return $results;
    }

    /**
     * Build player career stats by aggregating all season records for a person.
     *
     * @param array $filters Optional filters: person_id (required for meaningful results), limit
     * @return array<int, array{person: object, totals: array, seasons: int}>
     */
    public function searchPlayerCareerStats(array $filters = []): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');

        $query = $table->find()
            ->contain([
                'TeamSeasonRosters' => [
                    'Persons',
                    'TeamSeasons' => ['Teams', 'Seasons'],
                ],
            ])
            ->orderBy(['Seasons.start' => 'ASC']);

        if (!empty($filters['team_id'])) {
            $query->where(['TeamSeasons.team_id' => (int)$filters['team_id']]);
        }

        $rows = $query->all();

        // Group by person_id and aggregate
        $byPerson = [];
        foreach ($rows as $stat) {
            $roster = $stat->team_season_roster ?? null;
            if (!$roster || !$roster->person) {
                continue;
            }
            $personId = (int)$roster->person->id;
            if (!isset($byPerson[$personId])) {
                $byPerson[$personId] = [
                    'person' => $roster->person,
                    'totals' => $this->initializeStats('player'),
                    'seasons' => 0,
                ];
            }
            $this->addSeasonStats($byPerson[$personId]['totals'], $stat);
            $byPerson[$personId]['seasons']++;
        }

        // Sort by requested stat
        $sort = $filters['sort'] ?? 'PTS';
        $direction = strtoupper($filters['direction'] ?? 'DESC');
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'DESC';
        }
        $allowedSorts = array_keys($this->initializeStats('player'));
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'PTS';
        }

        usort($byPerson, function ($a, $b) use ($sort, $direction) {
            $va = $a['totals'][$sort] ?? 0;
            $vb = $b['totals'][$sort] ?? 0;

            return $direction === 'DESC' ? $vb <=> $va : $va <=> $vb;
        });

        $limit = (int)($filters['limit'] ?? 50);

        return $limit > 0 ? array_slice($byPerson, 0, min($limit, 5000)) : $byPerson;
    }

    /**
     * Get filter options for basketball stats (seasons and teams).
     *
     * @return array{seasons: array, teams: array}
     */
    public function getFilterOptions(): array
    {
        $seasonsTable = $this->fetchTable('Seasons');
        $seasons = $seasonsTable->find()
            ->orderBy(['Seasons.start' => 'DESC'])
            ->all()
            ->combine('id', function ($s) {
                return ($s->start ?? '') . '-' . ($s->end ?? '');
            })
            ->toArray();

        $teamsTable = $this->fetchTable('Teams');
        $teams = $teamsTable->find()
            ->matching('Sports', function ($q) {
                return $q->where(['Sports.sport_name' => 'Basketball']);
            })
            ->orderBy(['Teams.team_name' => 'ASC'])
            ->all()
            ->combine('id', 'team_name')
            ->toArray();

        return compact('seasons', 'teams');
    }

    /**
     * Add stat values from a game stat into a season stat entity.
     */
    private function addSeasonPersonStatValues(
        \App\Model\Entity\StatBasketSeasonPerson $seasonStat,
        \App\Model\Entity\StatBasketGamePerson $gameStat,
    ): void {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $add = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)($current + $add);
        }

        $currentPts = (int)($seasonStat->PTS ?? 0);
        $addPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = $currentPts + $addPts;
    }

    /**
     * Subtract stat values from a season stat entity.
     */
    private function subtractSeasonPersonStatValues(
        \App\Model\Entity\StatBasketSeasonPerson $seasonStat,
        \App\Model\Entity\StatBasketGamePerson $gameStat,
    ): void {
        $fields = ['GP', 'GS', 'MIN', 'FGM', 'FGA', 'TPM', 'TPA', 'FTM', 'FTA',
            'ORB', 'DRB', 'RB', 'AST', 'STL', 'BS', 'TRN', 'PF', 'TF'];

        foreach ($fields as $field) {
            $current = (int)($seasonStat->$field ?? 0);
            $subtract = (int)($gameStat->$field ?? 0);
            $seasonStat->$field = (string)max(0, $current - $subtract);
        }

        $currentPts = (int)($seasonStat->PTS ?? 0);
        $subtractPts = (int)($gameStat->PTS ?? 0);
        $seasonStat->PTS = max(0, $currentPts - $subtractPts);
    }
}
