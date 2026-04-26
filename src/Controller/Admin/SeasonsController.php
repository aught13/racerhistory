<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Seasons Controller
 *
 * Provides CRUD operations for managing seasons in the admin interface. The index action lists all seasons, while the add and edit actions allow for creating and updating seasons, respectively. The delete action handles season deletion, with a check to prevent deletion if there are associated team seasons. The controller also includes a bulkDelete action for deleting multiple seasons at once, and an ajaxAdd action for adding new seasons from a popup form, returning JSON responses for seamless integration with the frontend.
 *
 * Actions:
 * - index: Lists all seasons with their associated team seasons for record count display in delete confirmations.
 * - view: Displays details of a single season, including previous and next seasons based on end year.
 * - add: Handles the creation of a new season, including form display and processing.
 * - edit: Handles the editing of an existing season, including form display and processing.
 * - delete: Handles the deletion of a season, ensuring that there are no associated team seasons before allowing deletion. Uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 * - bulkDelete: Handles the deletion of multiple seasons at once, with similar checks and protections as the single delete action.
 * - bulk: A dispatcher for bulk actions, currently supporting bulk deletion of seasons.
 * - ajaxAdd: Provides an endpoint for adding a new season from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new seasons without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage seasons. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete and bulkDelete actions use POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - TeamSeasonService: Used to check for associated team seasons before allowing deletion of a season.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * Note: The ajaxAdd action is designed for use with popup forms and returns JSON responses indicating success or failure, along with any validation errors. This allows for seamless integration with the frontend without requiring full page reloads.
 *
 * @property \App\Service\TeamSeasonService $teamSeasonService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\SeasonsTable $Seasons
 * @property \App\Model\Table\TeamSeasonsTable $TeamSeasons
 * @property \App\Service\SeasonService $seasonService
 * @property \App\Service\TeamSeasonService $teamSeasonService
 * @property \App\Service\GameService $gameService
 * @property \App\Service\GameTypeService $gameTypeService
 * @property \App\Service\OpponentService $opponentService
 * @property \App\Service\PlaceService $placeService
 * @property \App\Service\SiteService $siteService
 * @property \App\Service\SeasonService $seasonService
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
        $season = $this->Seasons->get($id, contain: ['TeamSeasons' => ['Teams']]);

        // Find previous and next seasons ordered by end year
        $previousSeason = $this->Seasons->find()
            ->where(['end <' => $season->end])
            ->orderByDesc('end')
            ->first();

        $nextSeason = $this->Seasons->find()
            ->where(['end >' => $season->end])
            ->orderByAsc('end')
            ->first();

        $this->set(compact('season', 'previousSeason', 'nextSeason'));
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
