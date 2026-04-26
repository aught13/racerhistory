<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TeamSeasonRosterAdminService;
use Cake\Http\Response;

/**
 * TeamSeasonRosters Admin Controller
 *
 * Handles team-season roster administration requests and delegates all roster
 * orchestration to TeamSeasonRosterAdminService.
 *
 * Notes:
 * - Keep HTTP-only concerns here (method guards, flash messaging, redirects).
 * - Preserve exact flash strings used by integration tests.
 * - Extend TeamSeasonRosterAdminService before adding controller query logic.
 *
 * @property \App\Model\Table\TeamSeasonRostersTable $TeamSeasonRosters
 * @property \App\Service\TeamSeasonRosterAdminService $teamSeasonRosterAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class TeamSeasonRostersController extends AppController
{
    /**
     * Service that owns team-season roster admin orchestration.
     *
     * @var \App\Service\TeamSeasonRosterAdminService
     */
    protected TeamSeasonRosterAdminService $teamSeasonRosterAdminService;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->teamSeasonRosterAdminService = new TeamSeasonRosterAdminService();

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
        $this->set($this->teamSeasonRosterAdminService->getViewData($id));
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
        $this->set($this->teamSeasonRosterAdminService->getAddFormData($teamSeasonId));

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

        $result = $this->teamSeasonRosterAdminService->saveBulkAddRows($teamSeasonId, $rows);
        $saved = $result['saved'];
        $errors = $result['errors'];

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

        $this->set($this->teamSeasonRosterAdminService->getBulkEditFormData($teamSeasonId));

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
        $result = $this->teamSeasonRosterAdminService->processBulkUpdate($teamSeasonId, $rows);
        $saved = $result['saved'];
        $deletedCount = $result['deletedCount'];
        $errors = $result['errors'];

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
        $viewData = $this->teamSeasonRosterAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->teamSeasonRosterAdminService->saveExistingRoster($id, (array)$this->request->getData());
            $viewData['teamSeasonRoster'] = $result['teamSeasonRoster'];

            if ($result['success']) {
                $this->Flash->success(__('The team season roster has been saved.'));

                $tsId = (int)$result['teamSeasonRoster']->get('team_season_id');

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $tsId]);
            }
            $this->Flash->error(__('The team season roster could not be saved. Please, try again.'));
        }

        $this->set($viewData);

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
        $result = $this->teamSeasonRosterAdminService->deleteRoster($id);

        if ($result['success']) {
            $this->Flash->success(__('The team season roster has been deleted.'));
        } else {
            $this->Flash->error(__('The team season roster could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $result['teamSeasonId']]);
    }

    /**
     * Bulk delete multiple team season rosters.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $result = $this->teamSeasonRosterAdminService->bulkDeleteRosters(
            (array)$this->request->getData('team_season_roster_ids')
        );

        if (!$result['validSelection']) {
            $this->Flash->error('No team season rosters selected for deletion.');

            return $this->redirect($this->referer());
        }

        if (!$result['validRosters']) {
            $this->Flash->error('No valid team season rosters found for deletion.');

            return $this->redirect($this->referer());
        }

        $deletedCount = $result['deletedCount'];

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} team season roster(s).', $deletedCount));
        } else {
            $this->Flash->error('No team season rosters could be deleted.');
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $result['teamSeasonId']]);
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
        if ($this->request->is('post')) {
            $response = $this->teamSeasonRosterAdminService->createRosterFromPopup((array)$this->request->getData());
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
