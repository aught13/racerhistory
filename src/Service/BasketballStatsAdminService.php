<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Game;
use App\Model\Entity\StatBasketGameTeam;
use App\Model\Entity\TeamSeason;

/**
 * BasketballStatsAdminService
 *
 * Admin orchestration service for basketball stat management workflows.
 *
 * Human-readable role:
 * This service owns the application-level workflows behind the
 * `/admin/StatBasket*` controllers. It prepares view/form data, enforces
 * duplicate checks used by admin entry screens, persists basketball game and
 * season stat rows, and coordinates season-total reconciliation when an admin
 * action should affect cumulative totals.
 *
 * Agent-friendly guidance:
 * - Use this service for admin CRUD and bulk-entry orchestration.
 * - Use BasketballStatsService for shared basketball domain operations,
 *   public/read-oriented stat loading, and season-total mutation primitives.
 * - Keep controllers thin: request handling, flash messages, redirects here;
 *   table lookups and save orchestration belong in this service.
 */
class BasketballStatsAdminService extends BasketballStatsService
{
    /**
     * Get a game with the associations needed by the basketball admin controllers.
     *
     * @param int $gameId Game ID
     * @return \App\Model\Entity\Game
     */
    protected function getAdminGame(int $gameId): Game
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->get($gameId, contain: ['TeamSeason', 'Opponents']);

        return $game;
    }

    /**
     * Get a game with basketball sport context for admin box-score workflows.
     *
     * @param int $gameId Game ID
     * @return \App\Model\Entity\Game
     */
    protected function getAdminBasketballGame(int $gameId): Game
    {
        /** @var \App\Model\Table\GamesTable $gamesTable */
        $gamesTable = $this->fetchTable('Games');

        /** @var \App\Model\Entity\Game $game */
        $game = $gamesTable->find()
            ->contain(['TeamSeason' => ['Teams' => ['Sports']], 'Opponents'])
            ->where(['Games.id' => $gameId])
            ->firstOrFail();

        return $game;
    }

    /**
     * Get a team season with associations needed by admin stat forms.
     *
     * @param int $teamSeasonId Team season ID
     * @return \App\Model\Entity\TeamSeason
     */
    protected function getTeamSeasonForAdmin(int $teamSeasonId): TeamSeason
    {
        /** @var \App\Model\Table\TeamSeasonsTable $table */
        $table = $this->fetchTable('TeamSeasons');

        /** @var \App\Model\Entity\TeamSeason $teamSeason */
        $teamSeason = $table->get($teamSeasonId, contain: ['Teams', 'Seasons']);

        return $teamSeason;
    }

    /**
     * Get existing opponent player names for a game, normalized for duplicate checks.
     *
     * @param int $gameId Game ID
     * @return list<string>
     */
    protected function getExistingGameOpponentNames(int $gameId): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');

        $names = [];
        foreach ($table->find()->where(['game_id' => $gameId])->select(['name'])->all() as $row) {
            $names[] = strtolower(trim((string)$row->get('name')));
        }

        return $names;
    }

    /**
     * Normalize posted opponent player stat row data into entity fields.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $rowData Posted row data
     * @param string $name Normalized name value to persist
     * @return array<string, mixed>
     */
    protected function buildGameOpponentEntityData(int $gameId, array $rowData, string $name): array
    {
        return [
            'game_id' => $gameId,
            'name' => $name,
            'jersey' => $rowData['jersey'] ?? null,
            'position' => $rowData['position'] ?? null,
            'period' => $rowData['period'] ?? 'Z',
            'GP' => $rowData['GP'] ?? '1',
            'GS' => $rowData['GS'] ?? null,
            'MIN' => $rowData['MIN'] ?? null,
            'FGM' => $rowData['FGM'] ?? null,
            'FGA' => $rowData['FGA'] ?? null,
            'TPM' => $rowData['TPM'] ?? null,
            'TPA' => $rowData['TPA'] ?? null,
            'FTM' => $rowData['FTM'] ?? null,
            'FTA' => $rowData['FTA'] ?? null,
            'ORB' => $rowData['ORB'] ?? null,
            'DRB' => $rowData['DRB'] ?? null,
            'RB' => $rowData['RB'] ?? null,
            'AST' => $rowData['AST'] ?? null,
            'STL' => $rowData['STL'] ?? null,
            'BS' => $rowData['BS'] ?? null,
            'BD' => $rowData['BD'] ?? null,
            'TRN' => $rowData['TRN'] ?? null,
            'PF' => $rowData['PF'] ?? null,
            'TF' => $rowData['TF'] ?? null,
            'FD' => $rowData['FD'] ?? null,
            'PTS' => $rowData['PTS'] ?? null,
        ];
    }

    /**
     * Get or create a game team stat record.
     *
     * @param int $gameId Game ID
     * @param bool $opp Opponent flag
     * @return \App\Model\Entity\StatBasketGameTeam
     */
    protected function getOrCreateGameTeamStat(int $gameId, bool $opp): StatBasketGameTeam
    {
        /** @var \App\Model\Table\StatBasketGameTeamTable $table */
        $table = $this->fetchTable('StatBasketGameTeam');
        /** @var \App\Model\Entity\StatBasketGameTeam|null $stat */
        $stat = $table->find()->where([
            'StatBasketGameTeam.game_id' => $gameId,
            'StatBasketGameTeam.opp' => $opp,
        ])->first();

        if ($stat) {
            return $stat;
        }

        /** @var \App\Model\Entity\StatBasketGameTeam $stat */
        $stat = $table->newEmptyEntity();
        $stat->game_id = $gameId;
        $stat->opp = $opp;

        return $stat;
    }

    /**
     * Get or create a season stat entity keyed by team season.
     *
     * @param string $tableName Table alias
     * @param int $teamSeasonId Team season ID
     * @return object
     */
    protected function getOrCreateSeasonStatEntity(string $tableName, int $teamSeasonId): object
    {
        $table = $this->fetchTable($tableName);
        $stat = $table->find()->where(['team_season_id' => $teamSeasonId])->first();
        if ($stat) {
            return $stat;
        }

        $stat = $table->newEmptyEntity();
        $stat->set('team_season_id', $teamSeasonId);

        return $stat;
    }

    /**
     * Get field labels for basketball stats.
     *
     * @param int $sportId Sport ID
     * @return array<string, string>
     */
    protected function getBasketballFieldLabels(int $sportId): array
    {
        return (new SportConfigService())->getAllFieldLabels($sportId);
    }

    /**
     * Build roster option labels for a team season.
     *
     * @param int $teamSeasonId Team season ID
     * @return array<int, string>
     */
    protected function getTeamSeasonRosterOptions(int $teamSeasonId): array
    {
        /** @var \App\Model\Table\TeamSeasonRostersTable $rosterTable */
        $rosterTable = $this->fetchTable('TeamSeasonRosters');

        return $rosterTable->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $teamSeasonId])
            ->orderBy(['roster_number' => 'ASC'])
            ->all()
            ->combine('id', function ($row) {
                $person = $row->person;
                $name = $person->display ?? $person->full ?? '';
                $number = $row->roster_number ?? '';

                return ($number ? "#{$number} " : '') . $name;
            })
            ->toArray();
    }

    /**
     * Get roster ids that already have player stats for a game.
     *
     * @param int $gameId Game ID
     * @return list<int>
     */
    protected function getExistingGamePersonRosterIds(int $gameId): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');

        $rosterIds = [];
        foreach ($table->find()->where(['game_id' => $gameId])->select(['team_season_roster_id'])->all() as $row) {
            $rosterIds[] = (int)$row->get('team_season_roster_id');
        }

        return $rosterIds;
    }

    /**
     * Normalize posted player-game stat row data into entity fields.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $rowData Posted row data
     * @return array<string, mixed>
     */
    protected function buildGamePersonEntityData(int $gameId, array $rowData): array
    {
        return [
            'game_id' => $gameId,
            'team_season_roster_id' => (int)($rowData['team_season_roster_id'] ?? 0),
            'period' => $rowData['period'] ?? 'Z',
            'GP' => $rowData['GP'] ?? '1',
            'GS' => $rowData['GS'] ?? null,
            'MIN' => $rowData['MIN'] ?? null,
            'FGM' => $rowData['FGM'] ?? null,
            'FGA' => $rowData['FGA'] ?? null,
            'TPM' => $rowData['TPM'] ?? null,
            'TPA' => $rowData['TPA'] ?? null,
            'FTM' => $rowData['FTM'] ?? null,
            'FTA' => $rowData['FTA'] ?? null,
            'ORB' => $rowData['ORB'] ?? null,
            'DRB' => $rowData['DRB'] ?? null,
            'RB' => $rowData['RB'] ?? null,
            'AST' => $rowData['AST'] ?? null,
            'STL' => $rowData['STL'] ?? null,
            'BS' => $rowData['BS'] ?? null,
            'BD' => $rowData['BD'] ?? null,
            'TRN' => $rowData['TRN'] ?? null,
            'PF' => $rowData['PF'] ?? null,
            'TF' => $rowData['TF'] ?? null,
            'FD' => $rowData['FD'] ?? null,
            'PTS' => $rowData['PTS'] ?? null,
        ];
    }

    /**
     * Get admin view data for basketball player game stats.
     *
     * @param int $gameId Game ID
     * @return array{stats: iterable, game: \App\Model\Entity\Game}
     */
    public function getAdminGamePersonViewData(int $gameId): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');

        $stats = $table->find()
            ->contain(['TeamSeasonRosters' => ['Persons', 'TeamSeasons']])
            ->where(['StatBasketGamePerson.game_id' => $gameId])
            ->orderBy(function ($exp, $query) {
                return [
                    $query->newExpr('COALESCE(StatBasketGamePerson.GS, 0) DESC'),
                    $query->newExpr('COALESCE(StatBasketGamePerson.MIN, 0) DESC'),
                    'StatBasketGamePerson.PTS' => 'DESC',
                ];
            })
            ->all();

        return [
            'stats' => $stats,
            'game' => $this->getAdminGame($gameId),
        ];
    }

    /**
     * Get admin add-form data for basketball player game stats.
     *
     * @param int $gameId Game ID
     * @return array{game: \App\Model\Entity\Game, teamSeasonRoster: array<int, string>, alreadyAddedCount: int}
     */
    public function getAdminGamePersonAddData(int $gameId): array
    {
        $game = $this->getAdminGame($gameId);
        $allRoster = $this->getTeamSeasonRosterOptions((int)$game->team_season_id);
        $existingRosterIds = $this->getExistingGamePersonRosterIds($gameId);
        $alreadyAddedCount = count($existingRosterIds);
        $teamSeasonRoster = array_diff_key($allRoster, array_flip($existingRosterIds));

        return compact('game', 'teamSeasonRoster', 'alreadyAddedCount');
    }

    /**
     * Save multiple basketball player game stat rows.
     *
     * @param int $gameId Game ID
     * @param array<int, array<string, mixed>> $rows Posted rows
     * @param bool $addToTotals Whether to update season totals
     * @return array{saved: int, skipped: int, errors: array<int, string>, failedRows: array<int, array<string, mixed>>}
     */
    public function saveAdminGamePersonRows(int $gameId, array $rows, bool $addToTotals): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');

        $saved = 0;
        $skipped = 0;
        $errors = [];
        $failedRows = [];
        $existingRosterIdSet = array_flip($this->getExistingGamePersonRosterIds($gameId));
        $seenInBatch = [];

        foreach ($rows as $index => $rowData) {
            $rosterId = (int)($rowData['team_season_roster_id'] ?? 0);
            if (!$rosterId) {
                continue;
            }

            if (isset($existingRosterIdSet[$rosterId]) || isset($seenInBatch[$rosterId])) {
                $skipped++;
                continue;
            }
            $seenInBatch[$rosterId] = true;

            $entity = $table->newEntity($this->buildGamePersonEntityData($gameId, $rowData));
            if ($table->save($entity)) {
                $saved++;
                if ($addToTotals && $entity->team_season_roster_id && $entity->period === 'Z') {
                    $this->addGamePersonStatToSeasonTotals($entity);
                }
                continue;
            }

            $errors[] = __('Row {0}: could not save.', $index + 1);
            $failedRows[] = $rowData;
        }

        return compact('saved', 'skipped', 'errors', 'failedRows');
    }

    /**
     * Get admin edit-form data for a basketball player game stat.
     *
     * @param int $id Stat ID
     * @return array{stat: \App\Model\Entity\StatBasketGamePerson, game: \App\Model\Entity\Game, teamSeasonRoster: array<int, string>}
     */
    public function getAdminGamePersonEditData(int $id): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Entity\StatBasketGamePerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters', 'Games']);
        $game = $this->getAdminGame((int)$stat->game_id);
        $teamSeasonRoster = $this->getTeamSeasonRosterOptions((int)$game->team_season_id);

        return compact('stat', 'game', 'teamSeasonRoster');
    }

    /**
     * Update an existing basketball player game stat.
     *
     * @param int $id Stat ID
     * @param array<string, mixed> $data Submitted data
     * @param bool $addToTotals Whether to update season totals
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketGamePerson}
     */
    public function updateAdminGamePersonStat(int $id, array $data, bool $addToTotals): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Entity\StatBasketGamePerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters', 'Games']);
        $originalStat = clone $stat;
        $stat = $table->patchEntity($stat, $data);

        if (!$table->save($stat)) {
            return ['success' => false, 'stat' => $stat];
        }

        if ($addToTotals && $stat->team_season_roster_id && $stat->period === 'Z') {
            $this->updateGamePersonStatSeasonTotals($originalStat, $stat);
        }

        return ['success' => true, 'stat' => $stat];
    }

    /**
     * Get admin delete confirmation data for a basketball player game stat.
     *
     * @param int $id Stat ID
     * @return array{stat: \App\Model\Entity\StatBasketGamePerson, game: \App\Model\Entity\Game}
     */
    public function getAdminGamePersonDeleteData(int $id): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Entity\StatBasketGamePerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters' => ['Persons'], 'Games']);

        return [
            'stat' => $stat,
            'game' => $this->getAdminGame((int)$stat->game_id),
        ];
    }

    /**
     * Delete a basketball player game stat.
     *
     * @param int $id Stat ID
     * @param bool $deductFromTotals Whether to deduct from season totals
     * @return array{success: bool, gameId: int}
     */
    public function deleteAdminGamePersonStat(int $id, bool $deductFromTotals): array
    {
        /** @var \App\Model\Table\StatBasketGamePersonTable $table */
        $table = $this->fetchTable('StatBasketGamePerson');
        /** @var \App\Model\Entity\StatBasketGamePerson $stat */
        $stat = $table->get($id);
        $gameId = (int)$stat->game_id;
        $success = (bool)$table->delete($stat);

        if ($success && $deductFromTotals && (string)$stat->period === 'Z') {
            $this->removeGamePersonStatFromSeasonTotals($stat);
        }

        return compact('success', 'gameId');
    }

    /**
     * Get admin view data for basketball opponent player game stats.
     *
     * @param int $gameId Game ID
     * @return array{stats: iterable, game: \App\Model\Entity\Game}
     */
    public function getAdminGameOpponentViewData(int $gameId): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');

        return [
            'stats' => $table->find()
                ->where(['StatBasketGameOpponent.game_id' => $gameId])
                ->orderBy(['jersey' => 'ASC'])
                ->all(),
            'game' => $this->getAdminGame($gameId),
        ];
    }

    /**
     * Get admin add-form data for basketball opponent player game stats.
     *
     * @param int $gameId Game ID
     * @return array{game: \App\Model\Entity\Game}
     */
    public function getAdminGameOpponentAddData(int $gameId): array
    {
        return ['game' => $this->getAdminGame($gameId)];
    }

    /**
     * Save multiple basketball opponent player stat rows.
     *
     * @param int $gameId Game ID
     * @param array<int, array<string, mixed>> $rows Posted rows
     * @return array{saved: int, skipped: int, errors: array<int, string>, failedRows: array<int, array<string, mixed>>}
     */
    public function saveAdminGameOpponentRows(int $gameId, array $rows): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');

        $saved = 0;
        $skipped = 0;
        $errors = [];
        $failedRows = [];
        $existingNameSet = array_flip($this->getExistingGameOpponentNames($gameId));
        $seenInBatch = [];

        foreach ($rows as $index => $rowData) {
            $name = trim((string)($rowData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $nameKey = strtolower($name);
            if (isset($existingNameSet[$nameKey]) || isset($seenInBatch[$nameKey])) {
                $skipped++;
                continue;
            }
            $seenInBatch[$nameKey] = true;

            $entity = $table->newEntity($this->buildGameOpponentEntityData($gameId, $rowData, $name));
            if ($table->save($entity)) {
                $saved++;
                continue;
            }

            $errors[] = __('Row {0}: could not save.', $index + 1);
            $failedRows[] = $rowData;
        }

        return compact('saved', 'skipped', 'errors', 'failedRows');
    }

    /**
     * Get admin edit-form data for a basketball opponent player game stat.
     *
     * @param int $id Stat ID
     * @return array{stat: \App\Model\Entity\StatBasketGameOpponent, game: \App\Model\Entity\Game}
     */
    public function getAdminGameOpponentEditData(int $id): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');
        /** @var \App\Model\Entity\StatBasketGameOpponent $stat */
        $stat = $table->get($id, contain: ['Games']);

        return [
            'stat' => $stat,
            'game' => $this->getAdminGame((int)$stat->game_id),
        ];
    }

    /**
     * Update an existing basketball opponent player game stat.
     *
     * @param int $id Stat ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketGameOpponent}
     */
    public function updateAdminGameOpponentStat(int $id, array $data): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');
        /** @var \App\Model\Entity\StatBasketGameOpponent $stat */
        $stat = $table->get($id, contain: ['Games']);
        $stat = $table->patchEntity($stat, $data);

        return [
            'success' => (bool)$table->save($stat),
            'stat' => $stat,
        ];
    }

    /**
     * Delete a basketball opponent player game stat.
     *
     * @param int $id Stat ID
     * @return array{success: bool, gameId: int}
     */
    public function deleteAdminGameOpponentStat(int $id): array
    {
        /** @var \App\Model\Table\StatBasketGameOpponentTable $table */
        $table = $this->fetchTable('StatBasketGameOpponent');
        /** @var \App\Model\Entity\StatBasketGameOpponent $stat */
        $stat = $table->get($id);
        $gameId = (int)$stat->game_id;
        $success = (bool)$table->delete($stat);

        return compact('success', 'gameId');
    }

    /**
     * Get admin view data for basketball game team stats.
     *
     * @param int $gameId Game ID
     * @return array{teamStats: object|null, opponentStats: object|null, game: \App\Model\Entity\Game}
     */
    public function getAdminGameTeamViewData(int $gameId): array
    {
        /** @var \App\Model\Table\StatBasketGameTeamTable $table */
        $table = $this->fetchTable('StatBasketGameTeam');

        return [
            'teamStats' => $table->find()->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 0,
            ])->first(),
            'opponentStats' => $table->find()->where([
                'StatBasketGameTeam.game_id' => $gameId,
                'StatBasketGameTeam.opp' => 1,
            ])->first(),
            'game' => $this->getAdminGame($gameId),
        ];
    }

    /**
     * Get admin edit-form data for basketball game team stats.
     *
     * @param int $gameId Game ID
     * @return array{teamStats: \App\Model\Entity\StatBasketGameTeam, opponentStats: \App\Model\Entity\StatBasketGameTeam, game: \App\Model\Entity\Game}
     */
    public function getAdminGameTeamEditData(int $gameId): array
    {
        return [
            'teamStats' => $this->getOrCreateGameTeamStat($gameId, false),
            'opponentStats' => $this->getOrCreateGameTeamStat($gameId, true),
            'game' => $this->getAdminGame($gameId),
        ];
    }

    /**
     * Save basketball game team and opponent team stats together.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, teamStats: \App\Model\Entity\StatBasketGameTeam, opponentStats: \App\Model\Entity\StatBasketGameTeam, errors: array<int, string>}
     */
    public function saveAdminGameTeamStats(int $gameId, array $data): array
    {
        /** @var \App\Model\Table\StatBasketGameTeamTable $table */
        $table = $this->fetchTable('StatBasketGameTeam');
        $teamStats = $this->getOrCreateGameTeamStat($gameId, false);
        $opponentStats = $this->getOrCreateGameTeamStat($gameId, true);

        if (isset($data['team'])) {
            $teamStats = $table->patchEntity($teamStats, $data['team'] + ['game_id' => $gameId, 'opp' => false]);
        }
        if (isset($data['opponent'])) {
            $opponentData = $data['opponent'] + ['game_id' => $gameId, 'opp' => true];
            $opponentStats = $table->patchEntity($opponentStats, $opponentData);
        }

        $errors = [];
        $success = true;
        // Ensure explicit flags are set on the entities so blank input cannot
        // overwrite them during mass assignment.
        $teamStats->set('game_id', $gameId);
        $teamStats->set('opp', false);
        $opponentStats->set('game_id', $gameId);
        $opponentStats->set('opp', true);

        if (!$table->save($teamStats)) {
            $success = false;
            $errors[] = __('The team stats could not be saved. Please, try again.');
        }
        if (!$table->save($opponentStats)) {
            $success = false;
            $errors[] = __('The opponent stats could not be saved. Please, try again.');
        }

        return compact('success', 'teamStats', 'opponentStats', 'errors');
    }

    /**
     * Get admin add-form data for basketball player season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @return array{stat: \App\Model\Entity\StatBasketSeasonPerson, teamSeason: \App\Model\Entity\TeamSeason, teamSeasonRosters: array<int, string>}
     */
    public function getAdminSeasonPersonAddData(int $teamSeasonId): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $stat */
        $stat = $table->newEmptyEntity();

        return [
            'stat' => $stat,
            'teamSeason' => $this->getTeamSeasonForAdmin($teamSeasonId),
            'teamSeasonRosters' => $this->getTeamSeasonRosterOptions($teamSeasonId),
        ];
    }

    /**
     * Create a basketball player season stat.
     *
     * @param int $teamSeasonId Team season ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketSeasonPerson}
     */
    public function createAdminSeasonPersonStat(int $teamSeasonId, array $data): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $stat */
        $stat = $table->newEmptyEntity();
        $stat = $table->patchEntity($stat, $data);

        return [
            'success' => (bool)$table->save($stat),
            'stat' => $stat,
        ];
    }

    /**
     * Get admin edit-form data for a basketball player season stat.
     *
     * @param int $id Stat ID
     * @return array{stat: \App\Model\Entity\StatBasketSeasonPerson, teamSeason: \App\Model\Entity\TeamSeason|null, teamSeasonRosters: array<int, string>, teamSeasonId: int|null}
     */
    public function getAdminSeasonPersonEditData(int $id): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters' => ['Persons']]);
        $roster = $stat->team_season_roster;
        $teamSeasonId = $roster ? (int)$roster->team_season_id : null;

        return [
            'stat' => $stat,
            'teamSeason' => $teamSeasonId ? $this->getTeamSeasonForAdmin($teamSeasonId) : null,
            'teamSeasonRosters' => $teamSeasonId ? $this->getTeamSeasonRosterOptions($teamSeasonId) : [],
            'teamSeasonId' => $teamSeasonId,
        ];
    }

    /**
     * Update a basketball player season stat.
     *
     * @param int $id Stat ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketSeasonPerson, teamSeasonId: int|null}
     */
    public function updateAdminSeasonPersonStat(int $id, array $data): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters' => ['Persons']]);
        $teamSeasonId = $stat->team_season_roster ? (int)$stat->team_season_roster->team_season_id : null;
        $stat = $table->patchEntity($stat, $data);

        return [
            'success' => (bool)$table->save($stat),
            'stat' => $stat,
            'teamSeasonId' => $teamSeasonId,
        ];
    }

    /**
     * Delete a basketball player season stat.
     *
     * @param int $id Stat ID
     * @return array{success: bool, teamSeasonId: int|null}
     */
    public function deleteAdminSeasonPersonStat(int $id): array
    {
        /** @var \App\Model\Table\StatBasketSeasonPersonTable $table */
        $table = $this->fetchTable('StatBasketSeasonPerson');
        /** @var \App\Model\Entity\StatBasketSeasonPerson $stat */
        $stat = $table->get($id, contain: ['TeamSeasonRosters']);
        $teamSeasonId = $stat->team_season_roster ? (int)$stat->team_season_roster->team_season_id : null;
        $success = (bool)$table->delete($stat);

        return compact('success', 'teamSeasonId');
    }

    /**
     * Get admin edit-form data for basketball team season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @return array{stat: \App\Model\Entity\StatBasketSeasonTeam, teamSeason: \App\Model\Entity\TeamSeason}
     */
    public function getAdminSeasonTeamEditData(int $teamSeasonId): array
    {
        return [
            'stat' => $this->getOrCreateSeasonStatEntity('StatBasketSeasonTeam', $teamSeasonId),
            'teamSeason' => $this->getTeamSeasonForAdmin($teamSeasonId),
        ];
    }

    /**
     * Save basketball team season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketSeasonTeam}
     */
    public function saveAdminSeasonTeamStat(int $teamSeasonId, array $data): array
    {
        /** @var \App\Model\Table\StatBasketSeasonTeamTable $table */
        $table = $this->fetchTable('StatBasketSeasonTeam');
        /** @var \App\Model\Entity\StatBasketSeasonTeam $stat */
        $stat = $this->getOrCreateSeasonStatEntity('StatBasketSeasonTeam', $teamSeasonId);
        $stat = $table->patchEntity($stat, $data);

        return [
            'success' => (bool)$table->save($stat),
            'stat' => $stat,
        ];
    }

    /**
     * Delete basketball team season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @return bool
     */
    public function deleteAdminSeasonTeamStat(int $teamSeasonId): bool
    {
        /** @var \App\Model\Table\StatBasketSeasonTeamTable $table */
        $table = $this->fetchTable('StatBasketSeasonTeam');
        $stat = $table->find()->where(['team_season_id' => $teamSeasonId])->first();

        return $stat ? (bool)$table->delete($stat) : false;
    }

    /**
     * Get admin edit-form data for basketball opponent season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @return array{stat: \App\Model\Entity\StatBasketSeasonOpponent, teamSeason: \App\Model\Entity\TeamSeason}
     */
    public function getAdminSeasonOpponentEditData(int $teamSeasonId): array
    {
        return [
            'stat' => $this->getOrCreateSeasonStatEntity('StatBasketSeasonOpponent', $teamSeasonId),
            'teamSeason' => $this->getTeamSeasonForAdmin($teamSeasonId),
        ];
    }

    /**
     * Save basketball opponent season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, stat: \App\Model\Entity\StatBasketSeasonOpponent}
     */
    public function saveAdminSeasonOpponentStat(int $teamSeasonId, array $data): array
    {
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $table */
        $table = $this->fetchTable('StatBasketSeasonOpponent');
        /** @var \App\Model\Entity\StatBasketSeasonOpponent $stat */
        $stat = $this->getOrCreateSeasonStatEntity('StatBasketSeasonOpponent', $teamSeasonId);
        $stat = $table->patchEntity($stat, $data);

        return [
            'success' => (bool)$table->save($stat),
            'stat' => $stat,
        ];
    }

    /**
     * Delete basketball opponent season stats.
     *
     * @param int $teamSeasonId Team season ID
     * @return bool
     */
    public function deleteAdminSeasonOpponentStat(int $teamSeasonId): bool
    {
        /** @var \App\Model\Table\StatBasketSeasonOpponentTable $table */
        $table = $this->fetchTable('StatBasketSeasonOpponent');
        $stat = $table->find()->where(['team_season_id' => $teamSeasonId])->first();

        return $stat ? (bool)$table->delete($stat) : false;
    }

    /**
     * Get admin view data for basketball game box scores.
     *
     * @param int $gameId Game ID
     * @return array{game: \App\Model\Entity\Game, teamBox: object|null, opponentBox: object|null, fieldLabels: array<string, string>, hasPeriodStats: bool, isBasketball: bool}
     */
    public function getAdminGameBoxData(int $gameId): array
    {
        $game = $this->getAdminBasketballGame($gameId);
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        $opponentId = (int)($game->opponent_id ?? 0);
        $teamBox = $boxTable->find()->where(['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'])->first();
        $opponentBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'])
            ->first();
        $hasPeriodStats = $boxTable->find()->where(['game_id' => $gameId, 'period !=' => 'Z'])->count() > 0;
        $sportId = (int)$game->team_season->team->sport->id;

        return [
            'game' => $game,
            'teamBox' => $teamBox,
            'opponentBox' => $opponentBox,
            'fieldLabels' => $this->getBasketballFieldLabels($sportId),
            'hasPeriodStats' => $hasPeriodStats,
            'isBasketball' => strtolower((string)$game->team_season->team->sport->sport_name) === 'basketball',
        ];
    }

    /**
     * Save basketball final box scores.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, game: \App\Model\Entity\Game, redirectToPeriods: bool}
     */
    public function saveAdminGameBox(int $gameId, array $data): array
    {
        $game = $this->getAdminBasketballGame($gameId);
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        $opponentId = (int)($game->opponent_id ?? 0);
        $teamBox = $boxTable->find()->where(['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'])->first();
        $opponentBox = $boxTable->find()
            ->where(['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'])
            ->first();
        $originalTeamBox = $teamBox ? clone $teamBox : null;
        $originalOpponentBox = $opponentBox ? clone $opponentBox : null;
        $addToTotals = !empty($data['add_to_totals']);
        $teamMinutes = $addToTotals ? (int)($data['team_minutes'] ?? 0) : 0;

        if (!empty($data['team'])) {
            $teamData = $data['team'] + ['game_id' => $gameId, 'opponent_id' => 0, 'period' => 'Z'];
            if ($addToTotals) {
                $teamData['GP'] = '1';
                $teamData['MIN'] = (string)$teamMinutes;
            }
            $teamBox = $teamBox ? $boxTable->patchEntity($teamBox, $teamData) : $boxTable->newEntity($teamData);
            if (!$boxTable->save($teamBox)) {
                return ['success' => false, 'game' => $game, 'redirectToPeriods' => false];
            }
        }

        if (!empty($data['opponent'])) {
            $oppData = $data['opponent'] + ['game_id' => $gameId, 'opponent_id' => $opponentId, 'period' => 'Z'];
            if ($addToTotals) {
                $oppData['GP'] = '1';
                $oppData['MIN'] = (string)$teamMinutes;
            }
            $opponentBox = $opponentBox
                ? $boxTable->patchEntity($opponentBox, $oppData)
                : $boxTable->newEntity($oppData);
            if (!$boxTable->save($opponentBox)) {
                return ['success' => false, 'game' => $game, 'redirectToPeriods' => false];
            }
        }

        if ($addToTotals && $teamBox && $opponentBox) {
            $this->applyGameBoxToSeasonTotals($game, $teamBox, $opponentBox, $originalTeamBox, $originalOpponentBox);
        }

        return [
            'success' => true,
            'game' => $game,
            'redirectToPeriods' => !empty($data['add_periods']),
        ];
    }

    /**
     * Get admin view data for basketball period box scores.
     *
     * @param int $gameId Game ID
     * @return array{game: \App\Model\Entity\Game, numPeriods: int, numOT: int, existingStats: array<string, object>, fieldLabels: array<string, string>}
     */
    public function getAdminGameBoxPeriodsData(int $gameId): array
    {
        $game = $this->getAdminBasketballGame($gameId);
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        $existingStats = [];

        $statQuery = $boxTable->find()
            ->where(['game_id' => $gameId, 'period !=' => 'Z'])
            ->orderBy(['period' => 'ASC']);
        foreach ($statQuery->all() as $stat) {
            $key = ((int)$stat->get('opponent_id') === 0 ? 'team' : 'opponent') . '_' . (string)$stat->get('period');
            $existingStats[$key] = $stat;
        }

        return [
            'game' => $game,
            'numPeriods' => (int)($game->periods ?? 2),
            'numOT' => (int)($game->ot ?? 0),
            'existingStats' => $existingStats,
            'fieldLabels' => $this->getBasketballFieldLabels((int)$game->team_season->team->sport->id),
        ];
    }

    /**
     * Save basketball period box scores.
     *
     * @param int $gameId Game ID
     * @param array<string, mixed> $data Submitted data
     * @return array{success: bool, errors: array<int, string>}
     */
    public function saveAdminGameBoxPeriods(int $gameId, array $data): array
    {
        $viewData = $this->getAdminGameBoxPeriodsData($gameId);
        $game = $viewData['game'];
        /** @var \App\Model\Table\StatBasketGameBoxTable $boxTable */
        $boxTable = $this->fetchTable('StatBasketGameBox');
        $existingStats = $viewData['existingStats'];
        $numPeriods = $viewData['numPeriods'];
        $numOT = $viewData['numOT'];
        $opponentId = (int)($game->opponent_id ?? 0);
        $errors = [];

        for ($period = 1; $period <= $numPeriods; $period++) {
            $teamKey = 'team_' . $period;
            if (!empty($data[$teamKey])) {
                $entityData = $data[$teamKey] + ['game_id' => $gameId, 'opponent_id' => 0, 'period' => (string)$period];
                $entity = isset($existingStats[$teamKey])
                    ? $boxTable->patchEntity($existingStats[$teamKey], $entityData)
                    : $boxTable->newEntity($entityData);
                if (!$boxTable->save($entity)) {
                    $errors[] = "Team Period $period";
                }
            }

            $opponentKey = 'opponent_' . $period;
            if (!empty($data[$opponentKey])) {
                $entityData = $data[$opponentKey] + [
                    'game_id' => $gameId,
                    'opponent_id' => $opponentId,
                    'period' => (string)$period,
                ];
                $entity = isset($existingStats[$opponentKey])
                    ? $boxTable->patchEntity($existingStats[$opponentKey], $entityData)
                    : $boxTable->newEntity($entityData);
                if (!$boxTable->save($entity)) {
                    $errors[] = "Opponent Period $period";
                }
            }
        }

        for ($overtime = 1; $overtime <= $numOT; $overtime++) {
            $otPeriod = 'OT' . ($overtime > 1 ? $overtime : '');
            $teamKey = 'team_' . $otPeriod;
            if (!empty($data[$teamKey])) {
                $entityData = $data[$teamKey] + ['game_id' => $gameId, 'opponent_id' => 0, 'period' => $otPeriod];
                $entity = isset($existingStats[$teamKey])
                    ? $boxTable->patchEntity($existingStats[$teamKey], $entityData)
                    : $boxTable->newEntity($entityData);
                if (!$boxTable->save($entity)) {
                    $errors[] = "Team $otPeriod";
                }
            }

            $opponentKey = 'opponent_' . $otPeriod;
            if (!empty($data[$opponentKey])) {
                $entityData = $data[$opponentKey] + [
                    'game_id' => $gameId,
                    'opponent_id' => $opponentId,
                    'period' => $otPeriod,
                ];
                $entity = isset($existingStats[$opponentKey])
                    ? $boxTable->patchEntity($existingStats[$opponentKey], $entityData)
                    : $boxTable->newEntity($entityData);
                if (!$boxTable->save($entity)) {
                    $errors[] = "Opponent $otPeriod";
                }
            }
        }

        return [
            'success' => $errors === [],
            'errors' => $errors,
        ];
    }
}
