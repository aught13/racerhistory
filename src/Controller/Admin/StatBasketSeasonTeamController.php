<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonTeam Controller (Admin)
 *
 * Manages basketball team season statistics.
 *
 * @property \App\Model\Table\StatBasketSeasonTeamTable $StatBasketSeasonTeam
 */
class StatBasketSeasonTeamController extends AppController
{
    /**
     * Edit method - create or update team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        // Try to find existing stats, or create new
        $stat = $this->StatBasketSeasonTeam
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if (!$stat) {
            $stat = $this->StatBasketSeasonTeam->newEmptyEntity();
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);
            $stat->team_season_id = $teamSeasonId;
        }
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonTeam->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonTeam);
            if ($this->StatBasketSeasonTeam->save($stat)) {
                $this->Flash->success(__('The team season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The team season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);

        $this->set(compact('stat', 'teamSeason'));
    }

    /**
     * Delete method - remove team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonTeam
            ->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->first();

        if ($stat && $this->StatBasketSeasonTeam->delete($stat)) {
            $this->Flash->success(__('The team season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The team season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
