<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin TeamSeasonRosters Controller
 *
 * Handles administrative team season rosters management operations.
 * Provides functionality for team season rosters administration and CRUD operations.
 *
 * TeamSeasonRosters represent the relationship between team seasons and persons, capturing
 * detailed information about a person's participation in a specific team season including:
 * - Team season and person associations
 * - Roster details like number, position, height, weight
 *
 * @property \App\Model\Table\TeamSeasonRostersTable $TeamSeasonRosters
 */
class TeamSeasonRostersController extends AppController
{
    /**
     * View a single team season roster.
     *
     * @param string $id TeamSeasonRoster ID
     * @return void
     */
    public function view(string $id): void
    {
        $teamSeasonRoster = $this->TeamSeasonRosters->get(
            $id,
            ['contain' => [
                'TeamSeasons' => ['Teams', 'Seasons'],
                'Persons',
            ]]
        );
        $this->set(compact('teamSeasonRoster'));
    }

    /**
     * Add new team season roster form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        /** @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRoster */
        $teamSeasonRoster = $this->TeamSeasonRosters->newEmptyEntity();

        // Pre-populate team_season_id if provided in query string
        if ($this->request->getQuery('team_season_id')) {
            $teamSeasonRoster = $teamSeasonRoster->set(
                'team_season_id',
                (int)$this->request->getQuery('team_season_id')
            );
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $teamSeasonRoster = $this->TeamSeasonRosters->patchEntity($teamSeasonRoster, $data);

            if ($this->TeamSeasonRosters->save($teamSeasonRoster)) {
                $this->Flash->success(__('The team season roster has been saved.'));

                $tsId = (int)$teamSeasonRoster->get('team_season_id');

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $tsId]);
            }
            $this->Flash->error(__('The team season roster could not be saved. Please, try again.'));
        }

        $teamSeasonsQuery = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams', 'Seasons'])
            ->select(['id', 'Teams.team_name', 'Seasons.start', 'Seasons.end'])
            ->orderByDesc('Seasons.start')
            ->limit(200);

        $teamSeasonsList = [];
        foreach ($teamSeasonsQuery as $teamSeason) {
            /** @var \App\Model\Entity\TeamSeason $teamSeason */
            $teamName = $teamSeason->team->team_name;
            $seasonRange = $teamSeason->season->start . '-' . $teamSeason->season->end;
            $teamSeasonsList[$teamSeason->get('id')] = $teamName . ' (' . $seasonRange . ')';
        }
        $persons = $this->fetchTable('Persons')->find('list', limit: 200)->all();
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeasonRoster', 'teamSeasonsList', 'persons', 'sports'));

        return null;
    }

    /**
     * Edit team season roster form and processing.
     *
     * @param string $id TeamSeasonRoster ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        /** @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRoster */
        $teamSeasonRoster = $this->TeamSeasonRosters->get(
            $id,
            ['contain' => [
                'TeamSeasons' => ['Teams', 'Seasons'],
                'Persons',
            ]]
        );

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $teamSeasonRoster = $this->TeamSeasonRosters->patchEntity($teamSeasonRoster, $data);

            if ($this->TeamSeasonRosters->save($teamSeasonRoster)) {
                $this->Flash->success(__('The team season roster has been saved.'));

                $tsId = (int)$teamSeasonRoster->get('team_season_id');

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $tsId]);
            }
            $this->Flash->error(__('The team season roster could not be saved. Please, try again.'));
        }

        $teamSeasonsQuery = $this->fetchTable('TeamSeasons')->find()
            ->contain(['Teams', 'Seasons'])
            ->select(['id', 'Teams.team_name', 'Seasons.start', 'Seasons.end'])
            ->orderByDesc('Seasons.start')
            ->limit(200);

        $teamSeasonsList = [];
        foreach ($teamSeasonsQuery as $teamSeason) {
            $teamName = $teamSeason->team->team_name;
            $seasonRange = $teamSeason->season->start . '-' . $teamSeason->season->end;
            $teamSeasonsList[$teamSeason->get('id')] = $teamName . ' (' . $seasonRange . ')';
        }
        $persons = $this->fetchTable('Persons')->find('list', limit: 200)->all()->toArray();
        $personIdExisting = $teamSeasonRoster->get('person_id');
        if ($personIdExisting && !isset($persons[$personIdExisting])) {
            $person = $this->fetchTable('Persons')->get($personIdExisting);
            $persons[$person->get('id')] = (string)$person->get('display');
        }
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeasonRoster', 'teamSeasonsList', 'persons', 'sports'));

        return null;
    }

    /**
     * Delete a team season roster.
     *
     * @param string $id TeamSeasonRoster ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        /** @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRoster */
        $teamSeasonRoster = $this->TeamSeasonRosters->get($id);
        $teamSeasonId = (int)$teamSeasonRoster->get('team_season_id');

        if ($this->TeamSeasonRosters->delete($teamSeasonRoster)) {
            $this->Flash->success(__('The team season roster has been deleted.'));
        } else {
            $this->Flash->error(__('The team season roster could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }

    /**
     * Bulk delete multiple team season rosters.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $teamSeasonRosterIds = (array)$this->request->getData('team_season_roster_ids');
        // Remove empty/null/invalid values that can be introduced by placeholder hidden inputs
        $teamSeasonRosterIds = array_values(array_filter($teamSeasonRosterIds, function ($v) {
            return $v !== '' && $v !== null && ctype_digit((string)$v);
        }));

        if (empty($teamSeasonRosterIds)) {
            $this->Flash->error('No team season rosters selected for deletion.');

            return $this->redirect($this->referer());
        }

        /** @var \App\Model\Entity\TeamSeasonRosters|null $firstRoster */
        $firstRoster = $this->TeamSeasonRosters->find()->where(['id IN' => $teamSeasonRosterIds])->first();
        if (!$firstRoster) {
            $this->Flash->error('No valid team season rosters found for deletion.');

            return $this->redirect($this->referer());
        }
        $teamSeasonId = (int)$firstRoster->get('team_season_id');

        $deletedCount = $this->TeamSeasonRosters->deleteAll(['id IN' => $teamSeasonRosterIds]);

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} team season roster(s).', $deletedCount));
        } else {
            $this->Flash->error('No team season rosters could be deleted.');
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }

    /**
     * Bulk action dispatcher for team season rosters.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }

        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX add (popup form) endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        /** @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRoster */
        $teamSeasonRoster = $this->TeamSeasonRosters->newEmptyEntity();
        if ($this->request->is('post')) {
            $teamSeasonRoster = $this->TeamSeasonRosters->patchEntity($teamSeasonRoster, $this->request->getData());
            if ($this->TeamSeasonRosters->save($teamSeasonRoster)) {
                // Build person label using entity virtual field
                $personId = (int)$teamSeasonRoster->get('person_id');
                $personsTable = (new \Cake\ORM\Locator\TableLocator())->get('Persons');
                /** @var \App\Model\Entity\Person $person */
                $person = $personsTable->get($personId);
                $personLabel = $person->getLabel();
                $response = [
                    'success' => true,
                    'message' => 'Team season roster has been added successfully.',
                    'newOption' => [
                        'value' => (int)$teamSeasonRoster->get('id'),
                        'text' => $personLabel,
                    ],
                ];
            } else {
                $errors = [];
                foreach ($teamSeasonRoster->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errors[] = ucfirst($field) . ': ' . $error;
                    }
                }
                $response = [
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save team season roster. Please try again.'],
                ];
            }
        } else {
            $response = [
                'success' => false,
                'errors' => ['Invalid request method.'],
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($response));
    }
}
