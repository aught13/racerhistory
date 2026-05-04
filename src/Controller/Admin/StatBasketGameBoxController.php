<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use App\Service\SportConfigService;
use Cake\Http\Response;

/**
 * Admin controller for basketball game box-score workflows.
 *
 * The controller is responsible for request/response handling around final and
 * period-by-period box-score entry. BasketballStatsAdminService owns the
 * underlying loading, persistence, basketball-only checks, and season-total
 * update logic.
 *
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\StatBasketGameBoxTable $StatBasketGameBox
 */
class StatBasketGameBoxController extends AppController
{
    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected SportConfigService $SportConfig;

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
     * Edit/Create game box scores (final period only - period Z)
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function gameBox(int $gameId): ?Response
    {
        $viewData = $this->basketballStatsAdminService->getAdminGameBoxData($gameId);

        if (!$viewData['isBasketball']) {
            $this->Flash->error(__('Game box scores are currently only supported for basketball games.'));

            return $this->redirect(['controller' => 'Games', 'action' => 'edit', $gameId]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->basketballStatsAdminService->saveAdminGameBox($gameId, (array)$this->request->getData());
            if (!$result['success']) {
                $this->Flash->error(__('Could not save game box scores. Please try again.'));

                $this->set($viewData);

                return null;
            }

            $this->Flash->success(__('Game box scores have been saved.'));

            // If user wants to add period stats, redirect to period entry
            if ($result['redirectToPeriods']) {
                return $this->redirect(['action' => 'gameBoxPeriods', $gameId]);
            }

            return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
        }

        unset($viewData['isBasketball']);
        $this->set($viewData);

        return null;
    }

    /**
     * Edit period-by-period box scores
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function gameBoxPeriods(int $gameId): ?Response
    {
        $viewData = $this->basketballStatsAdminService->getAdminGameBoxPeriodsData($gameId);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->saveAdminGameBoxPeriods($gameId, $data);

            if ($result['success']) {
                $this->Flash->success(__('Period box scores have been saved.'));

                return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
            }

            $this->Flash->error(__('Could not save some period stats: {0}', implode(', ', $result['errors'])));
        }

        $this->set($viewData);

        return null;
    }
}
