<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TeamAdminService;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

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
     * Create the controller with its team administration service.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param \App\Service\TeamAdminService $teamAdminService The team administration service.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry.
     */
    public function __construct(
        ServerRequest $request,
        TeamAdminService $teamAdminService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
        $this->teamAdminService = $teamAdminService;
    }

    /**
     * Initialize controller dependencies.
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * List all teams for administration.
     *
     * @return void
     */
    public function index(): void
    {
        $sportKey = $this->request->getQuery('sport_key');
        $gender = $this->request->getQuery('gender');
        $this->set($this->teamAdminService->getIndexData(
            $this->request->getAttribute('identity'),
            is_string($sportKey) ? $sportKey : null,
            is_string($gender) ? $gender : null,
        ));
    }

    /**
     * View a single team.
     *
     * @param string $id Team ID
     * @return void
     */
    public function view(string $id): void
    {
        $this->set($this->teamAdminService->getViewData($id, $this->request->getAttribute('identity')));
    }

    /**
     * Add new team form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $sportRef = $this->request->getQuery('sport_key');
        if (!is_string($sportRef) || trim($sportRef) === '') {
            $legacySportId = $this->request->getQuery('sport_id');
            if (is_scalar($legacySportId) && trim((string)$legacySportId) !== '') {
                $sportRef = trim((string)$legacySportId);
            } else {
                $sportRef = null;
            }
        }

        $identity = $this->request->getAttribute('identity');
        $viewData = $this->teamAdminService->getAddFormData($sportRef, $identity);

        if ($this->request->is('post')) {
            $result = $this->teamAdminService->saveNewTeam((array)$this->request->getData(), $sportRef, $identity);
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
        $identity = $this->request->getAttribute('identity');
        $viewData = $this->teamAdminService->getEditFormData($id, $identity);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->teamAdminService->saveExistingTeam($id, (array)$this->request->getData(), $identity);
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
        $identity = $this->request->getAttribute('identity');

        if ($this->teamAdminService->deleteTeam($id, $identity)) {
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
        $identity = $this->request->getAttribute('identity');

        if (empty($teamIds)) {
            $this->Flash->error('No teams selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = $this->teamAdminService->bulkDeleteTeams($teamIds, $identity);

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
            $response = $this->teamAdminService->createTeamFromPopup(
                (array)$this->request->getData(),
                $this->request->getAttribute('identity'),
            );
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
