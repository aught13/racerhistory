<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PersonService;
use App\Service\TeamSeasonService;
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
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig(
                'unlockedActions',
                array_merge($current, ['bulkAdd', 'bulkEdit'])
            );
        }
    }

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
     * Add new team season roster form (multi-row).
     *
     * Displays a form with one or more roster entry rows. Users can add rows
     * dynamically and submit all at once via the bulkAdd action.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $teamSeasonId = $this->request->getQuery('team_season_id')
            ? (int)$this->request->getQuery('team_season_id')
            : null;

        $teamSeasonsList = (new TeamSeasonService())->getTeamSeasonsListForRosterSelect(200);
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeasonId', 'teamSeasonsList', 'sports'));

        return null;
    }

    /**
     * Bulk add multiple roster entries at once.
     *
     * Accepts an array of roster row data and saves each as a new entity.
     * Redirects back to team season view on success.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulkAdd(): ?Response
    {
        $this->request->allowMethod(['post']);

        $rows = (array)$this->request->getData('rows');
        $teamSeasonId = (int)$this->request->getData('team_season_id');

        if (empty($rows) || !$teamSeasonId) {
            $this->Flash->error(__('No roster entries to save.'));

            return $this->redirect(['action' => 'add', '?' => ['team_season_id' => $teamSeasonId ?: null]]);
        }

        $saved = 0;
        $errors = [];
        foreach ($rows as $i => $rowData) {
            // Skip completely empty rows (no person selected)
            $personId = (int)($rowData['person_id'] ?? 0);
            if (!$personId) {
                continue;
            }

            $entityData = [
                'team_season_id' => $teamSeasonId,
                'person_id' => $personId,
                'roster_year' => $rowData['roster_year'] ?? null,
                'roster_number' => $rowData['roster_number'] ?? null,
                'roster_position' => $rowData['roster_position'] ?? null,
                'roster_height' => $rowData['roster_height'] ?? null,
                'roster_weight' => $rowData['roster_weight'] ?? null,
            ];

            $entity = $this->TeamSeasonRosters->newEntity($entityData);
            if ($this->TeamSeasonRosters->save($entity)) {
                $saved++;
            } else {
                $errors[] = __('Row {0}: could not save.', $i + 1);
            }
        }

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} roster entry/entries.', $saved));
        }
        if (!empty($errors)) {
            $this->Flash->error(implode(' ', $errors));
        }

        if ($saved > 0) {
            return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
        }

        return $this->redirect(['action' => 'add', '?' => ['team_season_id' => $teamSeasonId]]);
    }

    /**
     * Bulk edit form – loads existing roster entries for a team season.
     *
     * GET shows the multi-row form pre-populated with current roster data.
     * POST (via bulkUpdate) saves changes and deletions.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulkEdit(): ?Response
    {
        $teamSeasonId = $this->request->getQuery('team_season_id')
            ? (int)$this->request->getQuery('team_season_id')
            : null;

        if ($this->request->is(['post', 'put', 'patch'])) {
            return $this->_processBulkUpdate($teamSeasonId);
        }

        $existingRosters = [];
        if ($teamSeasonId) {
            $existingRosters = $this->TeamSeasonRosters->find()
                ->where(['team_season_id' => $teamSeasonId])
                ->contain(['Persons'])
                ->orderByAsc('roster_number')
                ->all()
                ->toArray();
        }

        $teamSeasonsList = (new TeamSeasonService())->getTeamSeasonsListForRosterSelect(200);
        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();

        $this->set(compact('teamSeasonId', 'teamSeasonsList', 'sports', 'existingRosters'));

        return null;
    }

    /**
     * Process the bulk edit POST: update existing rows, create new rows, delete removed rows.
     *
     * @param int|null $teamSeasonId Team season ID
     * @return \Cake\Http\Response
     */
    private function _processBulkUpdate(?int $teamSeasonId): Response
    {
        $rows = (array)$this->request->getData('rows');
        $teamSeasonId = (int)$this->request->getData('team_season_id');

        if (!$teamSeasonId) {
            $this->Flash->error(__('Invalid team season.'));

            return $this->redirect(['action' => 'bulkEdit']);
        }

        // Gather IDs that are still present in the form submission
        $submittedIds = [];
        foreach ($rows as $rowData) {
            $existingId = (int)($rowData['id'] ?? 0);
            if ($existingId) {
                $submittedIds[] = $existingId;
            }
        }

        // Delete records that were in the roster but removed from the form
        $allExistingIds = $this->TeamSeasonRosters->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->all()
            ->extract('id')
            ->toArray();

        $toDelete = array_diff($allExistingIds, $submittedIds);
        $deletedCount = 0;
        if (!empty($toDelete)) {
            $deletedCount = $this->TeamSeasonRosters->deleteAll([
                'id IN' => array_values($toDelete),
                'team_season_id' => $teamSeasonId,
            ]);
        }

        // Save/update remaining rows
        $saved = 0;
        $errors = [];
        foreach ($rows as $i => $rowData) {
            $personId = (int)($rowData['person_id'] ?? 0);
            if (!$personId) {
                continue;
            }

            $existingId = (int)($rowData['id'] ?? 0);
            $entityData = [
                'team_season_id' => $teamSeasonId,
                'person_id' => $personId,
                'roster_year' => $rowData['roster_year'] ?? null,
                'roster_number' => $rowData['roster_number'] ?? null,
                'roster_position' => $rowData['roster_position'] ?? null,
                'roster_height' => $rowData['roster_height'] ?? null,
                'roster_weight' => $rowData['roster_weight'] ?? null,
            ];

            if ($existingId) {
                // Update existing record
                $entity = $this->TeamSeasonRosters->find()
                    ->where(['id' => $existingId, 'team_season_id' => $teamSeasonId])
                    ->first();
                if (!$entity) {
                    $errors[] = __('Row {0}: record not found.', $i + 1);
                    continue;
                }
                $entity = $this->TeamSeasonRosters->patchEntity($entity, $entityData);
            } else {
                // Create new record
                $entity = $this->TeamSeasonRosters->newEntity($entityData);
            }

            if ($this->TeamSeasonRosters->save($entity)) {
                $saved++;
            } else {
                $errors[] = __('Row {0}: could not save.', $i + 1);
            }
        }

        $messages = [];
        if ($saved > 0) {
            $messages[] = __('Saved {0} roster entry/entries.', $saved);
        }
        if ($deletedCount > 0) {
            $messages[] = __('Removed {0} roster entry/entries.', $deletedCount);
        }
        if (!empty($messages)) {
            $this->Flash->success(implode(' ', $messages));
        }
        if (!empty($errors)) {
            $this->Flash->error(implode(' ', $errors));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
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
            contain: [
                'TeamSeasons' => ['Teams', 'Seasons'],
                'Persons',
            ]
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

        $teamSeasonsList = (new TeamSeasonService())->getTeamSeasonsListForRosterSelect(200);

        $personIdExisting = (int)$teamSeasonRoster->get('person_id');
        $persons = (new PersonService())->getPersonsList(200, $personIdExisting ?: null);
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
                $personId = (int)$teamSeasonRoster->get('person_id');
                $personLabel = (new PersonService())->getDisplayLabel($personId);
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
