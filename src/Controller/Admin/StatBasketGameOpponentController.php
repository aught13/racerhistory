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
     * Add method - create new opponent player stat entry
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $gameId)
    {
        $stat = $this->StatBasketGameOpponent->newEmptyEntity();
        assert($stat instanceof \App\Model\Entity\StatBasketGameOpponent);
        $stat->game_id = $gameId;
        $stat->period = 'Z'; // Default to final stats
        $stat->GP = '1'; // Default games played to 1

        if ($this->request->is('post')) {
            $stat = $this->StatBasketGameOpponent->patchEntity($stat, $this->request->getData());
            if ($this->StatBasketGameOpponent->save($stat)) {
                $this->Flash->success(__('The opponent player stat has been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
            $this->Flash->error(__('The opponent player stat could not be saved. Please, try again.'));
        }

        $game = $this->fetchTable('Games')->get($gameId, contain: ['TeamSeason', 'Opponents']);

        $this->set(compact('stat', 'game'));
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
