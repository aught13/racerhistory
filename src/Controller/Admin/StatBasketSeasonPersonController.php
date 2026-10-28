<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Admin controller for basketball player season stat workflows.
 *
 * This controller manages request handling for add, edit, and delete flows for
 * season-player stat rows. BasketballStatsAdminService provides the form data,
 * roster options, persistence, and lookup of the owning team season.
 *
 * @property \App\Model\Table\StatBasketSeasonPersonTable $StatBasketSeasonPerson
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketSeasonPersonController extends AppController
{
    private BasketballStatsAdminService $basketballStatsAdminService;

    /**
     * Create the controller with its basketball stats administration service.
     *
     * @param \Cake\Http\ServerRequest $request The current request.
     * @param \App\Service\BasketballStatsAdminService $basketballStatsAdminService The basketball stats administration service.
     * @param \Cake\Controller\ComponentRegistry|null $components The component registry.
     */
    public function __construct(
        ServerRequest $request,
        BasketballStatsAdminService $basketballStatsAdminService,
        ?ComponentRegistry $components = null,
    ) {
        parent::__construct($request, null, null, $components);
        $this->basketballStatsAdminService = $basketballStatsAdminService;
    }

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * Add method - create new player season stat entry
     *
     * @param int $teamSeasonId Team Season ID
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add(int $teamSeasonId)
    {
        $viewData = $this->basketballStatsAdminService->getAdminSeasonPersonAddData($teamSeasonId);
        $stat = $viewData['stat'];

        if ($this->request->is('post')) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->createAdminSeasonPersonStat($teamSeasonId, $data);
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Edit method - update existing player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $viewData = $this->basketballStatsAdminService->getAdminSeasonPersonEditData($id);
        $stat = $viewData['stat'];
        $teamSeasonId = $viewData['teamSeasonId'];
        if (!$teamSeasonId) {
            $this->Flash->error(__('Unable to find team season roster for this stat.'));

            return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->updateAdminSeasonPersonStat($id, $data);
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The player season stats have been saved.'));

                return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
            }
            $this->Flash->error(__('The player season stats could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Delete method - remove player season stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to team season view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $result = $this->basketballStatsAdminService->deleteAdminSeasonPersonStat($id);
        $teamSeasonId = $result['teamSeasonId'];

        if ($result['success']) {
            $this->Flash->success(__('The player season stats have been deleted.'));
        } else {
            $this->Flash->error(__('The player season stats could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'TeamSeasons', 'action' => 'view', $teamSeasonId]);
    }
}
