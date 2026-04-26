<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsService;
use Cake\Http\Response;

/**
 * StatBasketGamePerson Controller (Admin)
 *
 * Manages basketball player game statistics. Provides actions to view stats for a specific game, add new stats in bulk, edit existing stats, and delete stats with optional deduction from season totals. The controller interacts with the StatBasketGamePerson model and uses the BasketballStatsService for handling season totals updates when adding or deleting stats. Proper validation, error handling, and user feedback are implemented throughout the controller to ensure a smooth user experience when managing basketball game statistics in the admin interface.
 * The view action displays all player stats for a specific game, ordered by games started, minutes played, and points scored. The add action provides a form for adding multiple player stats at once, while the bulkAdd action processes the submitted data and saves the new stats to the database. The edit action allows for updating an existing stat entry, and the delete action handles the removal of a stat entry with an option to deduct the values from season totals if applicable. All actions are protected by appropriate HTTP method checks and provide feedback through flash messages to inform the user of the success or failure of their actions.
 *
 * Actions:
 * - view: Displays player stats for a specific game, ordered by games started, minutes played, and points scored.
 * - add: Renders a form for adding multiple player stats for a specific game, excluding players who already have stats for that game.
 * - bulkAdd: Processes the submitted data from the add form and saves new player stats to the database, with an option to add the stats to season totals.
 * - edit: Allows for editing an existing player stat entry, with an option to update season totals if applicable.
 * - deleteConfirm: Renders a confirmation page before deleting a player stat entry, providing details about the stat and the associated game.
 * - delete: Handles the deletion of a player stat entry, with an option to deduct the values from season totals if applicable.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage basketball game statistics. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The bulkAdd and delete actions use POST HTTP methods to prevent unintended modifications via GET requests. Proper validation should be implemented for all input data to prevent invalid or malicious data from being saved to the database, and appropriate error handling should be in place to provide feedback to the user in case of issues with their input or with the database operations. Flash messages are used to inform the user of the success or failure of their actions, enhancing the user experience when managing basketball game statistics in the admin interface.
 *
 * Dependencies:
 * - BasketballStatsService: Provides methods for updating season totals when adding or deleting player stats, abstracting away the details of these operations from the controller and allowing for cleaner code and easier maintenance.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after add, edit, and delete operations, providing feedback to the user about the outcome of their actions. This enhances the user experience by clearly communicating the results of their interactions with the basketball game statistics management features in the admin interface.
 *
 * @property \App\Model\Table\StatBasketGamePersonTable $StatBasketGamePerson
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\BasketballStatsService $basketballStatsService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGamePersonController extends AppController
{
    private BasketballStatsService $basketballStatsService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->basketballStatsService = new BasketballStatsService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig(
                'unlockedActions',
                array_merge($current, ['bulkAdd', 'delete', 'deleteConfirm'])
            );
        }
    }

    /**
     * View method - display stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $stats = $this->StatBasketGamePerson
            ->find()
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

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('stats', 'game'));
    }

    /**
     * Add method - displays multi-row form for adding player stats.
     *
     * GET renders the bulk add form with one empty row. Players who already have
     * stats for this game are excluded from the roster dropdown.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function add(int $gameId)
    {
        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        // Build full roster for this team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $game->team_season_id])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $allRoster = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        // Find roster IDs that already have stats for this game
        /** @var list<int> $existingRosterIds */
        $existingRosterIds = $this->StatBasketGamePerson
            ->find()
            ->where(['game_id' => $gameId])
            ->select(['team_season_roster_id'])
            ->all()
            ->map(fn($row) => $row->team_season_roster_id)
            ->toList();

        $alreadyAddedCount = count($existingRosterIds);

        // Exclude players who already have stats for this game
        $existingRosterIdIndex = array_flip($existingRosterIds);
        $teamSeasonRoster = array_diff_key($allRoster, $existingRosterIdIndex);

        $this->set(compact('game', 'teamSeasonRoster', 'alreadyAddedCount'));
    }

    /**
     * Bulk add multiple player stat entries at once.
     *
     * Accepts an array of stat row data and saves each as a new entity.
     * Optionally adds each stat to season totals when the checkbox is checked.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function bulkAdd(int $gameId): ?Response
    {
        $this->request->allowMethod(['post']);

        $rows = (array)$this->request->getData('rows');
        $addToTotals = (bool)$this->request->getData('add_to_totals');

        if (empty($rows)) {
            $this->Flash->error(__('No player stats to save.'));

            return $this->redirect(['action' => 'add', $gameId]);
        }

        $saved = 0;
        $skipped = 0;
        $errors = [];
        $failedRows = [];

        // Find roster IDs that already have stats for this game to prevent duplicates
        /** @var list<int> $existingRosterIds */
        $existingRosterIds = $this->StatBasketGamePerson
            ->find()
            ->where(['game_id' => $gameId])
            ->select(['team_season_roster_id'])
            ->all()
            ->map(fn($row) => $row->team_season_roster_id)
            ->toList();

        $existingRosterIdSet = array_flip($existingRosterIds);
        $seenInBatch = [];

        foreach ($rows as $i => $rowData) {
            $rosterId = (int)($rowData['team_season_roster_id'] ?? 0);
            if (!$rosterId) {
                continue;
            }

            // Skip if this player already has stats for this game (existing or in current batch)
            if (isset($existingRosterIdSet[$rosterId]) || isset($seenInBatch[$rosterId])) {
                $skipped++;
                continue;
            }
            $seenInBatch[$rosterId] = true;

            $entityData = [
                'game_id' => $gameId,
                'team_season_roster_id' => $rosterId,
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

            $entity = $this->StatBasketGamePerson->newEntity($entityData);
            if ($this->StatBasketGamePerson->save($entity)) {
                $saved++;
                // Handle add-to-totals if checkbox was selected
                if ($addToTotals && $entity->team_season_roster_id && $entity->period === 'Z') {
                    $this->basketballStatsService->addGamePersonStatToSeasonTotals($entity);
                }
            } else {
                $errors[] = __('Row {0}: could not save.', $i + 1);
                $failedRows[] = $rowData;
            }
        }

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} player stat(s).', $saved));
        }
        if ($skipped > 0) {
            $msg = __('Skipped {0} player(s) that already have stats for this game.', $skipped);
            $this->Flash->warning($msg);
        }
        if (!empty($errors)) {
            $this->Flash->error(implode(' ', $errors));
        }

        // On success (at least one saved, no errors) redirect to game view
        if ($saved > 0 && empty($errors)) {
            return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
        }

        // On failure: fall back to the add page with errored rows
        if (!empty($failedRows)) {
            $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);
            assert($game instanceof \App\Model\Entity\Game);

            $roster = $this->fetchTable('TeamSeasonRosters')
                ->find()
                ->contain(['Persons'])
                ->where(['team_season_id' => $game->team_season_id])
                ->orderBy(['roster_number' => 'ASC'])
                ->all();

            $allRoster = $roster->combine('id', function ($row) {
                $person = $row->person;
                $name = $person->display ?? $person->full ?? '';
                $number = $row->roster_number ?? '';

                return ($number ? "#{$number} " : '') . $name;
            })->toArray();

            /** @var list<int> $alreadyUsedIds */
            $alreadyUsedIds = $this->StatBasketGamePerson
                ->find()
                ->where(['game_id' => $gameId])
                ->select(['team_season_roster_id'])
                ->all()
                ->map(fn($row) => $row->team_season_roster_id)
                ->toList();

            $alreadyAddedCount = count($alreadyUsedIds);
            $teamSeasonRoster = array_diff_key($allRoster, array_flip($alreadyUsedIds));

            $this->set(compact('game', 'teamSeasonRoster', 'alreadyAddedCount', 'failedRows'));

            return $this->render('add');
        }

        return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
    }

    /**
     * Edit method - update existing player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketGamePerson->get($id, contain: ['TeamSeasonRosters', 'Games']);
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);

        // Store original stat values for comparison if editing
        $originalStat = clone $stat;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketGamePerson->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);
            if ($this->StatBasketGamePerson->save($stat)) {
                // Handle add-to-totals if checkbox was selected
                $addToTotals = $this->request->getData('add_to_totals');
                if ($addToTotals && $stat->team_season_roster_id && $stat->period === 'Z') {
                    $this->basketballStatsService->updateGamePersonStatSeasonTotals($originalStat, $stat);
                }

                $this->Flash->success(__('The player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($stat->game_id, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        // Get roster for this game's team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $game->team_season_id])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRoster = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'game', 'teamSeasonRoster'));
    }

    /**
     * Delete confirm method - renders a confirmation page before deleting.
     *
     * Prompts the user to confirm the deletion and optionally deduct the
     * stat from season totals.
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function deleteConfirm(int $id)
    {
        $stat = $this->StatBasketGamePerson->get(
            $id,
            contain: ['TeamSeasonRosters' => ['Persons'], 'Games']
        );
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);

        $game = $this->fetchTable('Games')->get($stat->game_id, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        $this->set(compact('stat', 'game'));
    }

    /**
     * Delete method - remove player stat entry
     *
     * Accepts an optional `deduct_from_totals` POST param. When set and the
     * stat has period 'Z', the values are subtracted from the player's season
     * totals before deletion.
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketGamePerson->get($id);
        assert($stat instanceof \App\Model\Entity\StatBasketGamePerson);
        $gameId = $stat->game_id;
        $deductFromTotals = (bool)$this->request->getData('deduct_from_totals');

        if ($this->StatBasketGamePerson->delete($stat)) {
            if ($deductFromTotals && (string)$stat->period === 'Z') {
                $this->basketballStatsService->removeGamePersonStatFromSeasonTotals($stat);
            }
            $this->Flash->success(__('The player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $gameId]);
    }
}
