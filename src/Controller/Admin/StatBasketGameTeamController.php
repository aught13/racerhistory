<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;

/**
 * Admin controller for basketball team-level game stat workflows.
 *
 * The controller handles the HTTP flow for viewing and editing team and
 * opponent team-stat rows for a game. BasketballStatsAdminService performs the
 * lookup, entity preparation, and coordinated save logic.
 *
 * @property \App\Model\Table\StatBasketGameTeamTable $StatBasketGameTeam
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGameTeamController extends AppController
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
     * View method - display team stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $this->set($this->basketballStatsAdminService->getAdminGameTeamViewData($gameId));
    }

    /**
     * Edit method - update team stats (both team and opponent)
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $gameId)
    {
        $viewData = $this->basketballStatsAdminService->getAdminGameTeamEditData($gameId);
        $teamStats = $viewData['teamStats'];
        $opponentStats = $viewData['opponentStats'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->saveAdminGameTeamStats($gameId, $data);
            $teamStats = $result['teamStats'];
            $opponentStats = $result['opponentStats'];
            foreach ($result['errors'] as $message) {
                $this->Flash->error($message);
            }

            if ($result['success']) {
                $this->Flash->success(__('The team stats have been saved.'));

                return $this->redirect(['action' => 'view', $gameId]);
            }
        }

        $this->set($viewData + compact('teamStats', 'opponentStats'));
    }
}
