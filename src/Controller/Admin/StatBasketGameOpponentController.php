<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketGameOpponent Controller (Admin)
 *
 * Manages basketball opponent player game statistics. Provides actions to view, add, edit, and delete opponent stats for a specific game. The view action displays all opponent stats for a given game, while the add action allows for bulk adding multiple opponent stat entries at once. The edit action allows for updating an existing opponent stat entry, and the delete action removes an opponent stat entry. The bulkAdd action processes multiple stat entries submitted from the add form, saving valid entries and providing feedback on any skipped or errored entries. Proper validation and error handling are implemented to ensure data integrity and provide a user-friendly experience in managing opponent stats for basketball games in the admin interface.
 *
 * Actions:
 * - view: Displays all opponent stats for a specific game.
 * - add: Renders a form for bulk adding opponent stats for a specific game.
 * - bulkAdd: Processes the submitted bulk add form data, saving valid entries and providing feedback on the results.
 * - edit: Allows editing of an existing opponent stat entry.
 * - delete: Deletes a specific opponent stat entry.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage opponent stats. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 * - The bulkAdd action validates the input data to prevent invalid or malicious data from being saved to the database, and provides feedback on any issues encountered during the save process.
 *
 * Dependencies:
 * - SportConfigService: Used to retrieve sport-specific configurations, which may influence how stats are processed or displayed.
 * - BasketballStatsService: Provides methods for calculating basketball-specific statistics, which may be used in the view or processing of opponent stats.
 *
 * Components:
 * - FlashComponent: Used to set success, warning, and error messages after processing actions, providing feedback to the user on the results of their actions, such as how many stats were saved, skipped, or if any errors occurred during the bulk add process.
 *
 * @property \App\Model\Table\StatBasketGameOpponentTable $StatBasketGameOpponent
 * @property \App\Service\SportConfigService $SportConfig
 * @property \App\Service\BasketballStatsService $basketballStatsService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGameOpponentController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig(
                'unlockedActions',
                array_merge($current, ['bulkAdd'])
            );
        }
    }

    /**
     * View method - display opponent stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $stats = $this->StatBasketGameOpponent
            ->find()
            ->where(['StatBasketGameOpponent.game_id' => $gameId])
            ->orderBy(['jersey' => 'ASC'])
            ->all();

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('stats', 'game'));
    }

    /**
     * Add method - displays multi-row form for adding opponent stats.
     *
     * GET renders the bulk add form with one empty row.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function add(int $gameId)
    {
        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        $this->set(compact('game'));
    }

    /**
     * Bulk add multiple opponent player stat entries at once.
     *
     * Accepts an array of stat row data and saves each as a new entity.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function bulkAdd(int $gameId): ?Response
    {
        $this->request->allowMethod(['post']);

        $rows = (array)$this->request->getData('rows');

        if (empty($rows)) {
            $this->Flash->error(__('No opponent stats to save.'));

            return $this->redirect(['action' => 'add', $gameId]);
        }

        $saved = 0;
        $skipped = 0;
        $errors = [];
        $failedRows = [];

        // Collect opponent names that already exist for this game (case-insensitive)
        /** @var list<string> $existingNames */
        $existingNames = $this->StatBasketGameOpponent
            ->find()
            ->where(['game_id' => $gameId])
            ->select(['name'])
            ->all()
            ->map(fn($row) => strtolower(trim((string)$row->name)))
            ->toList();

        $existingNameSet = array_flip($existingNames);
        $seenInBatch = [];

        foreach ($rows as $i => $rowData) {
            $name = trim((string)($rowData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $nameKey = strtolower($name);

            // Skip if this opponent player already has stats for this game
            if (isset($existingNameSet[$nameKey]) || isset($seenInBatch[$nameKey])) {
                $skipped++;
                continue;
            }
            $seenInBatch[$nameKey] = true;

            $entityData = [
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

            $entity = $this->StatBasketGameOpponent->newEntity($entityData);
            if ($this->StatBasketGameOpponent->save($entity)) {
                $saved++;
            } else {
                $errors[] = __('Row {0}: could not save.', $i + 1);
                $failedRows[] = $rowData;
            }
        }

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} opponent stat(s).', $saved));
        }
        if ($skipped > 0) {
            $msg = __('Skipped {0} opponent player(s) that already have stats for this game.', $skipped);
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
            $this->set(compact('game', 'failedRows'));

            return $this->render('add');
        }

        return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
    }

    /**
     * Edit method - update existing opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketGameOpponent->get($id, contain: ['Games']);
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketGameOpponent->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
            if ($this->StatBasketGameOpponent->save($stat)) {
                $this->Flash->success(__('The opponent player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The opponent player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($stat->game_id, contain: ['TeamSeason', 'Opponents']);
        assert($game instanceof \App\Model\Entity\Game);

        $this->set(compact('stat', 'game'));
    }

    /**
     * Delete method - remove opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketGameOpponent->get($id);
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
        $gameId = $stat->game_id;

        if ($this->StatBasketGameOpponent->delete($stat)) {
            $this->Flash->success(__('The opponent player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The opponent player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $gameId]);
    }
}
