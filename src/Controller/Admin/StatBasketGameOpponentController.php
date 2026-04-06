<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketGameOpponent Controller (Admin)
 *
 * Manages basketball opponent player game statistics.
 *
 * @property \App\Model\Table\StatBasketGameOpponentTable $StatBasketGameOpponent
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
        $errors = [];
        foreach ($rows as $i => $rowData) {
            $name = trim((string)($rowData['name'] ?? ''));
            if ($name === '') {
                continue;
            }

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
            }
        }

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} opponent stat(s).', $saved));
        }
        if (!empty($errors)) {
            $this->Flash->error(implode(' ', $errors));
        }

        if ($saved > 0) {
            return $this->redirect(['action' => 'view', $gameId]);
        }

        return $this->redirect(['action' => 'add', $gameId]);
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
