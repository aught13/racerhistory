<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonOpponent Controller (Admin)
 *
 * Manages basketball opponent season statistics.
 *
 * @property \App\Model\Table\StatBasketSeasonOpponentTable $StatBasketSeasonOpponent
 */
class StatBasketSeasonOpponentController extends AppController
{
    /**
     * Edit method - create or update opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        // Try to find existing stats, or create new
        $stat = $this->StatBasketSeasonOpponent
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if (!$stat) {
            $stat = $this->StatBasketSeasonOpponent->newEmptyEntity();
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);
            $stat->team_season_id = $teamSeasonId;
        }
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonOpponent->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonOpponent);
            if ($this->StatBasketSeasonOpponent->save($stat)) {
                $this->Flash->success(__('The opponent season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The opponent season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);

        $this->set(compact('stat', 'teamSeason'));
    }

    /**
     * Delete method - remove opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonOpponent
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if ($stat && $this->StatBasketSeasonOpponent->delete($stat)) {
            $this->Flash->success(__('The opponent season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The opponent season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
