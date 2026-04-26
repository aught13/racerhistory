<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TeamAdminService;
use Cake\Http\Response;

/**
 * Teams Admin Controller
 *
 * Human-focused summary:
 * Handles HTTP orchestration for team administration while delegating all
 * domain and persistence work to TeamAdminService. This keeps controller
 * actions concise, predictable, and aligned with service-layer architecture.
 *
 * Agent-focused maintenance notes:
 * - Keep request/response concerns in this class only.
 * - Add or modify team workflows in TeamAdminService first.
 * - Preserve flash message text where tests assert exact strings.
 *
 * @property \App\Model\Table\TeamsTable $Teams
 * @property \App\Service\TeamAdminService $teamAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class TeamsController extends AppController
{
    /**
     * Service that owns the admin team orchestration slice.
     *
     * @var \App\Service\TeamAdminService
     */
    protected TeamAdminService $teamAdminService;

    /**
     * Initialize controller dependencies.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->teamAdminService = new TeamAdminService();
    }

    /**
     * List all teams for administration.
     *
     * @return void
     */
    public function index(): void
    {
        $this->set($this->teamAdminService->getIndexData());
    }

    /**
     * View a single team.
     *
     * @param string $id Team ID
     * @return void
     */
    public function view(string $id): void
    {
        $this->set($this->teamAdminService->getViewData($id));
    }

    /**
     * Add new team form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $sportId = $this->request->getQuery('sport_id')
            ? (int)$this->request->getQuery('sport_id')
            : null;
        $viewData = $this->teamAdminService->getAddFormData($sportId);

        if ($this->request->is('post')) {
            $result = $this->teamAdminService->saveNewTeam((array)$this->request->getData(), $sportId);
            $viewData['team'] = $result['team'];

            if ($result['success']) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }

        $this->set($viewData);

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
        $viewData = $this->teamAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->teamAdminService->saveExistingTeam($id, (array)$this->request->getData());
            $viewData['team'] = $result['team'];

            if ($result['success']) {
                $this->Flash->success(__('The team has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team could not be saved. Please, try again.'));
        }

        $this->set($viewData);

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

        if ($this->teamAdminService->deleteTeam($id)) {
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
        $teamIds = $this->teamAdminService->sanitizeIdentifierList((array)$this->request->getData('team_ids'));

        if (empty($teamIds)) {
            $this->Flash->error('No teams selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = $this->teamAdminService->bulkDeleteTeams($teamIds);

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
        if ($this->request->is('post')) {
            $response = $this->teamAdminService->createTeamFromPopup((array)$this->request->getData());
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
