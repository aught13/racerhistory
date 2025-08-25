<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Seasons Controller
 *
 * Handles administrative seasons management operations.
 * Provides functionality for seasons administration and CRUD operations.
 *
 * Seasons represent time periods during which teams compete. They define the academic
 * or calendar year structure for organizing sports activities. Each season has:
 * - Start and end year designations
 * - Multiple team seasons associated with it
 *
 * @property \App\Model\Table\SeasonsTable $Seasons
 */
class SeasonsController extends AppController
{
    /**
     * List all seasons for administration.
     *
     * @return void
     */
    public function index()
    {
        // Include TeamSeasons so templates can surface associated record counts in delete confirmations
        $seasons = $this->Seasons->find()->contain(['TeamSeasons'])->all();

        $this->set(compact('seasons'));
    }

    /**
     * View a single season.
     *
     * @param string $id Season ID
     * @return void
     */
    public function view(string $id)
    {
        $season = $this->Seasons->get($id, contain: ['TeamSeasons']);
        $this->set(compact('season'));
    }

    /**
     * Add new season form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        $season = $this->Seasons->newEmptyEntity();

        if ($this->request->is('post')) {
            $season = $this->Seasons->patchEntity($season, $this->request->getData());

            if ($this->Seasons->save($season)) {
                $this->Flash->success(__('The season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The season could not be saved. Please, try again.'));
        }

        $this->set(compact('season'));

        return null;
    }

    /**
     * Edit season form and processing.
     *
     * @param string $id Season ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id)
    {
        // Contain TeamSeasons so we can show associated record counts in the confirmation modal
        $season = $this->Seasons->get($id, contain: ['TeamSeasons']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $season = $this->Seasons->patchEntity($season, $this->request->getData());

            if ($this->Seasons->save($season)) {
                $this->Flash->success(__('The season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The season could not be saved. Please, try again.'));
        }

        $this->set(compact('season'));

        return null;
    }

    /**
     * Delete a season.
     *
     * @param string $id Season ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $season = $this->Seasons->get($id);

        if ($this->Seasons->delete($season)) {
            $this->Flash->success(__('The season has been deleted.'));
        } else {
            $this->Flash->error(__('The season could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete multiple seasons.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete()
    {
        $this->request->allowMethod(['post']);
        $seasonIds = (array)$this->request->getData('season_ids');
        $seasonIds = array_values(array_filter($seasonIds, function ($v) {
            return $v !== '' && $v !== null && ctype_digit((string)$v);
        }));

        if (empty($seasonIds)) {
            $this->Flash->error('No seasons selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = 0;
        foreach ($seasonIds as $id) {
            try {
                $season = $this->Seasons->get($id);

                if ($this->Seasons->delete($season)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $e) {
                continue;
            }
        }

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} season(s).', $deletedCount));
        } else {
            $this->Flash->error('No seasons could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for seasons.
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
     * AJAX endpoint for adding seasons from popup forms.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $season = $this->Seasons->newEmptyEntity();

        if ($this->request->is('post')) {
            $season = $this->Seasons->patchEntity($season, $this->request->getData());

            if ($this->Seasons->save($season)) {
                $response = [
                    'success' => true,
                    'message' => 'Season has been added successfully.',
                    'newOption' => [
                        'value' => $season->id,
                        'text' => $season->start . '-' . $season->end,
                    ],
                ];
            } else {
                $errors = [];
                foreach ($season->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errors[] = ucfirst($field) . ': ' . $error;
                    }
                }

                $response = [
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save season. Please try again.'],
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
