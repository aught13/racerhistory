<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use Cake\Http\Response;

/**
 * Admin controller for basketball team season stat workflows.
 *
 * The controller exposes edit and delete endpoints for season-team stat rows
 * while BasketballStatsAdminService handles entity lookup/creation and persistence.
 *
 * @property \App\Model\Table\StatBasketSeasonTeamTable $StatBasketSeasonTeam
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonTeamController extends AppController
{
    private BasketballStatsAdminService $basketballStatsAdminService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->basketballStatsAdminService = new BasketballStatsAdminService();
    }

    /**
     * Edit method - create or update team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        $viewData = $this->basketballStatsAdminService->getAdminSeasonTeamEditData($teamSeasonId);
        $stat = $viewData['stat'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->saveAdminSeasonTeamStat($teamSeasonId, $data);
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The team season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The team season stats could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Delete method - remove team season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if ($this->basketballStatsAdminService->deleteAdminSeasonTeamStat($teamSeasonId)) {
            $this->Flash->success(__('The team season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The team season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
