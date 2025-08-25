<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Teams Controller
 *
 * Handles administrative teams management operations.
 * Provides functionality for teams administration and CRUD operations.
 *
 * Teams represent individual competitive units within a sport. Each team
 * belongs to a specific sport and has classification information including:
 * - Team name and optional description
 * - Sport association (required)
 * - Abbreviation for compact display
 * - Gender classification (Male, Female, or Co-ed)
 *
 * @property \App\Model\Table\TeamsTable $Teams
 */
class TeamsController extends AppController
{
    /**
     * List all teams for administration.
     *
     * @return void
     */
    public function index(): void
    {
        $teams = $this->Teams->find()
            ->contain(['Sports']) // already needed for listing
            ->all();
        $this->set(compact('teams'));
    }

    /**
     * View a single team.
     *
     * @param string $id Team ID
     * @return void
     */
    public function view(string $id): void
    {
    // Use named arguments for get() to avoid deprecation warnings
        $team = $this->Teams->get($id, contain: ['Sports']);

        $this->set(compact('team'));
    }

    /**
     * Add new team form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $team = $this->Teams->newEmptyEntity();

        // Pre-populate sport_id if provided in query string
        if ($this->request->getQuery('sport_id')) {
            $team->sport_id = (int)$this->request->getQuery('sport_id');
        }

        if ($this->request->is('post')) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());

            if ($this->Teams->save($team)) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }

        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();
        $this->set(compact('team', 'sports'));

        return null;
    }

    /**
     * Edit team form and processing.
     *
     * @param string $id Team ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        $team = $this->Teams->get($id, contain: ['Sports']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());

            if ($this->Teams->save($team)) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }

        $sports = $this->fetchTable('Sports')->find('list', limit: 200)->all();
        $this->set(compact('team', 'sports'));

        return null;
    }

    /**
     * Delete a team.
     *
     * @param string $id Team ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $team = $this->Teams->get($id);

        if ($this->Teams->delete($team)) {
            $this->Flash->success(__('The team has been deleted.'));
        } else {
            $this->Flash->error(__('The team could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete multiple teams.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $teamIds = (array)$this->request->getData('team_ids');
        // Remove empty/null/invalid values that can be introduced by placeholder hidden inputs
        $teamIds = array_values(array_filter($teamIds, function ($v) {
            return $v !== '' && $v !== null && ctype_digit((string)$v);
        }));

        if (empty($teamIds)) {
            $this->Flash->error('No teams selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = 0;
        foreach ($teamIds as $id) {
            try {
                $team = $this->Teams->get($id);

                if ($this->Teams->delete($team)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $e) {
                // Skip invalid id silently; could log if needed
                continue;
            }
        }

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} team(s).', $deletedCount));
        } else {
            $this->Flash->error('No teams could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for teams.
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
     * AJAX endpoint for adding teams from popup forms.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $team = $this->Teams->newEmptyEntity();

        if ($this->request->is('post')) {
            $team = $this->Teams->patchEntity($team, $this->request->getData());

            if ($this->Teams->save($team)) {
                $response = [
                    'success' => true,
                    'message' => 'Team has been added successfully.',
                    'newOption' => [
                        'value' => $team->id,
                        'text' => $team->team_name,
                    ],
                ];
            } else {
                $errors = [];
                foreach ($team->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errors[] = ucfirst($field) . ': ' . $error;
                    }
                }

                $response = [
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save team. Please try again.'],
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
