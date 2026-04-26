<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SeasonAdminService;
use Cake\Http\Response;

/**
 * Seasons Admin Controller
 *
 * Provides season administration endpoints and delegates all data orchestration
 * to SeasonAdminService, leaving this controller focused on HTTP concerns.
 *
 * Notes:
 * - Add new season business rules in SeasonAdminService first.
 * - Keep flash message text stable where tests assert exact values.
 * - Preserve JSON response structure for popup creation flows.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\SeasonsTable $Seasons
 * @property \App\Service\SeasonAdminService $seasonAdminService
 */
class SeasonsController extends AppController
{
    /**
     * Service that owns the admin season orchestration slice.
     *
     * @var \App\Service\SeasonAdminService
     */
    protected SeasonAdminService $seasonAdminService;

    /**
     * Initialize controller dependencies.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->seasonAdminService = new SeasonAdminService();
    }

    /**
     * List all seasons for administration.
     *
     * @return void
     */
    public function index()
    {
        $this->set($this->seasonAdminService->getIndexData());
    }

    /**
     * View a single season.
     *
     * @param string $id Season ID
     * @return void
     */
    public function view(string $id)
    {
        $this->set($this->seasonAdminService->getViewData($id));
    }

    /**
     * Add new season form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        $viewData = $this->seasonAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->seasonAdminService->saveNewSeason((array)$this->request->getData());
            $viewData['season'] = $result['season'];

            if ($result['success']) {
                $this->Flash->success(__('The season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The season could not be saved. Please, try again.'));
        }

        $this->set($viewData);

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
        $viewData = $this->seasonAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->seasonAdminService->saveExistingSeason($id, (array)$this->request->getData());
            $viewData['season'] = $result['season'];

            if ($result['success']) {
                $this->Flash->success(__('The season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The season could not be saved. Please, try again.'));
        }

        $this->set($viewData);

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

        if ($this->seasonAdminService->deleteSeason($id)) {
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
        $seasonIds = $this->seasonAdminService->sanitizeIdentifierList((array)$this->request->getData('season_ids'));

        if (empty($seasonIds)) {
            $this->Flash->error('No seasons selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = $this->seasonAdminService->bulkDeleteSeasons($seasonIds);

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
        if ($this->request->is('post')) {
            $response = $this->seasonAdminService->createSeasonFromPopup((array)$this->request->getData());
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
