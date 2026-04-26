<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use Cake\Http\Response;

/**
 * Admin controller for basketball opponent season stat workflows.
 *
 * Request handling for edit and delete stays here, while
 * BasketballStatsAdminService is responsible for loading, creating, updating,
 * and removing the underlying season-opponent stat entities.
 *
 * @property \App\Model\Table\StatBasketSeasonOpponentTable $StatBasketSeasonOpponent
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonOpponentController extends AppController
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
     * Edit method - create or update opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $teamSeasonId)
    {
        $viewData = $this->basketballStatsAdminService->getAdminSeasonOpponentEditData($teamSeasonId);
        $stat = $viewData['stat'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->basketballStatsAdminService->saveAdminSeasonOpponentStat($teamSeasonId, (array)$this->request->getData());
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The opponent season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The opponent season stats could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Delete method - remove opponent season stats
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $teamSeasonId): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        if ($this->basketballStatsAdminService->deleteAdminSeasonOpponentStat($teamSeasonId)) {
            $this->Flash->success(__('The opponent season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The opponent season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
