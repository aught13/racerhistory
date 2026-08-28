<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\TeamSeasonAdminService;
use Cake\Http\Response;

/**
 * TeamSeasons Admin Controller
 *
 * Exposes admin endpoints for team-season lifecycle management while
 * delegating query composition and persistence orchestration to
 * TeamSeasonAdminService.
 *
 * Notes:
 * - Keep this controller transport-focused (request guards, flash, redirect).
 * - Add team-season business rules in TeamSeasonAdminService, not here.
 * - Preserve output variable names expected by templates and integration tests.
 *
 * @property \App\Model\Table\TeamSeasonsTable $TeamSeasons
 * @property \App\Service\TeamSeasonAdminService $teamSeasonAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class TeamSeasonsController extends AppController
{
    /**
     * Service that owns the admin team-season orchestration slice.
     *
     * @var \App\Service\TeamSeasonAdminService
     */
    protected TeamSeasonAdminService $teamSeasonAdminService;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            if (!in_array('delete', $current, true)) {
                $current[] = 'delete';
                $this->FormProtection->setConfig('unlockedActions', $current);
            }
        }

        $this->teamSeasonAdminService = new TeamSeasonAdminService();
    }

    /**
     * List all team seasons for administration.
     *
     * @return void
     */
    public function index(): void
    {
        $this->set($this->teamSeasonAdminService->getIndexData());
    }

    /**
     * View a single team season.
     *
     * @param string $id TeamSeason ID
     * @return void
     */
    public function view(string $id): void
    {
        $this->set($this->teamSeasonAdminService->getViewData($id));
    }

    /**
     * Add new team season form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $teamId = $this->request->getQuery('team_id') ? (int)$this->request->getQuery('team_id') : null;
        $seasonId = $this->request->getQuery('season_id') ? (int)$this->request->getQuery('season_id') : null;
        $viewData = $this->teamSeasonAdminService->getAddFormData($teamId, $seasonId);

        if ($this->request->is('post')) {
            $result = $this->teamSeasonAdminService->saveNewTeamSeason(
                (array)$this->request->getData(),
                $teamId,
                $seasonId,
            );
            $viewData['teamSeason'] = $result['teamSeason'];

            if ($result['success']) {
                $this->Flash->success(__('The team season has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The team season could not be saved. Please, try again.'));
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Edit team season form and processing.
     *
     * @param string $id TeamSeason ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->teamSeasonAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->teamSeasonAdminService->saveExistingTeamSeason($id, (array)$this->request->getData());
            $viewData['teamSeason'] = $result['teamSeason'];

            if ($result['success']) {
                $this->Flash->success(__('The team season has been saved.'));

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('The team season could not be saved. Please, try again.'));
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Delete a team season.
     *
     * @param string $id TeamSeason ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $identity = $this->request->getAttribute('identity');

        if ($this->teamSeasonAdminService->deleteTeamSeason($id, $identity)) {
            $this->Flash->success(__('The team season has been deleted.'));
        } else {
            $this->Flash->error(__('The team season could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete multiple team seasons.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $this->Flash->error('Bulk delete is no longer available for team seasons.');

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for team seasons.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $this->request->allowMethod(['post']);

        return $this->bulkDelete();
    }
}
