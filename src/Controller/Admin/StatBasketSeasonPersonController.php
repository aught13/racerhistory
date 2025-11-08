<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * StatBasketSeasonPerson Controller (Admin)
 *
 * Manages basketball player season statistics.
 *
 * @property \App\Model\Table\StatBasketSeasonPersonTable $StatBasketSeasonPerson
 */
class StatBasketSeasonPersonController extends AppController
{


    /**
     * Add method - create new player season stat entry
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $teamSeasonId)
    {
        $stat = $this->StatBasketSeasonPerson->newEmptyEntity();
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);

        if ($this->request->is('post')) {
            $stat = $this->StatBasketSeasonPerson->patchEntity($stat, $this->request->getData());
            if ($this->StatBasketSeasonPerson->save($stat)) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
        assert($teamSeason instanceof \App\Model\Entity\TeamSeason);

        // Get roster for this team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $teamSeasonId])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRosters = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'teamSeason', 'teamSeasonRosters'));
    }

    /**
     * Edit method - update existing player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $stat = $this->StatBasketSeasonPerson->get($id, contain: ['TeamSeasonRosters' => ['Persons']]);
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);

        // Get team_season_id from roster
        $roster = $stat->team_season_roster ?? null;
        if (!$roster) {
            $this->Flash->error(__('Unable to find team season roster for this stat.'));

            return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'index']);
        }
        assert($roster instanceof \App\Model\Entity\TeamSeasonRosters);
        $teamSeasonId = $roster->team_season_id;

        if ($this->request->is(['patch', 'post', 'put'])) {
            $stat = $this->StatBasketSeasonPerson->patchEntity($stat, $this->request->getData());
            assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);
            if ($this->StatBasketSeasonPerson->save($stat)) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $teamSeason = $this->fetchTable('TeamSeasons')->get($teamSeasonId, contain: ['Teams', 'Seasons']);
        assert($teamSeason instanceof \App\Model\Entity\TeamSeason);

        // Get roster for this team season
        $roster = $this->fetchTable('TeamSeasonRosters')
            ->find()
            ->contain(['Persons'])
            ->where(['team_season_id' => $teamSeasonId])
            ->orderBy(['roster_number' => 'ASC'])
            ->all();

        $teamSeasonRosters = $roster->combine('id', function ($row) {
            $person = $row->person;
            $name = $person->display ?? $person->full ?? '';
            $number = $row->roster_number ?? '';

            return ($number ? "#{$number} " : '') . $name;
        })->toArray();

        $this->set(compact('stat', 'teamSeason', 'teamSeasonRosters'));
    }

    /**
     * Delete method - remove player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $stat = $this->StatBasketSeasonPerson->get($id, contain: ['TeamSeasonRosters']);
        assert($stat instanceof \App\Model\Entity\StatBasketSeasonPerson);
        $roster = $stat->team_season_roster ?? null;
        $teamSeasonId = null;
        if ($roster) {
            assert($roster instanceof \App\Model\Entity\TeamSeasonRosters);
            $teamSeasonId = $roster->team_season_id;
        }

        if ($this->StatBasketSeasonPerson->delete($stat)) {
            $this->Flash->success(__('The player season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The player season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
