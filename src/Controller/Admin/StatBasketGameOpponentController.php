<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use Cake\Http\Response;

/**
 * Admin controller for basketball opponent player game stat workflows.
 *
 * Request validation, flash messaging, and redirects stay here. Loading,
 * duplicate detection, and save/update/delete orchestration for opponent rows
 * are delegated to BasketballStatsAdminService.
 *
 * @property \App\Model\Table\StatBasketGameOpponentTable $StatBasketGameOpponent
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGameOpponentController extends AppController
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

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig(
                'unlockedActions',
                array_merge($current, ['bulkAdd'])
            );
        }
    }

    /**
     * View method - display opponent stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $this->set($this->basketballStatsAdminService->getAdminGameOpponentViewData($gameId));
    }

    /**
     * Add method - displays multi-row form for adding opponent stats.
     *
     * GET renders the bulk add form with one empty row.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function add(int $gameId)
    {
        $this->set($this->basketballStatsAdminService->getAdminGameOpponentAddData($gameId));
    }

    /**
     * Bulk add multiple opponent player stat entries at once.
     *
     * Accepts an array of stat row data and saves each as a new entity.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function bulkAdd(int $gameId): ?Response
    {
        $this->request->allowMethod(['post']);

        $rows = (array)$this->request->getData('rows');

        if (empty($rows)) {
            $this->Flash->error(__('No opponent stats to save.'));

            return $this->redirect(['action' => 'add', $gameId]);
        }

        $result = $this->basketballStatsAdminService->saveAdminGameOpponentRows($gameId, $rows);
        $saved = $result['saved'];
        $skipped = $result['skipped'];
        $errors = $result['errors'];
        $failedRows = $result['failedRows'];

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} opponent stat(s).', $saved));
        }
        if ($skipped > 0) {
            $msg = __('Skipped {0} opponent player(s) that already have stats for this game.', $skipped);
            $this->Flash->warning($msg);
        }
        if (!empty($errors)) {
            $this->Flash->error(implode(' ', $errors));
        }

        // On success (at least one saved, no errors) redirect to game view
        if ($saved > 0 && empty($errors)) {
            return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
        }

        // On failure: fall back to the add page with errored rows
        if (!empty($failedRows)) {
            $addData = $this->basketballStatsAdminService->getAdminGameOpponentAddData($gameId);
            $this->set($addData + compact('failedRows'));

            return $this->render('add');
        }

        return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
    }

    /**
     * Edit method - update existing opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $viewData = $this->basketballStatsAdminService->getAdminGameOpponentEditData($id);
        $stat = $viewData['stat'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = (array)$this->request->getData();
            $result = $this->basketballStatsAdminService->updateAdminGameOpponentStat($id, $data);
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The opponent player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The opponent player stat could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Delete method - remove opponent player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $result = $this->basketballStatsAdminService->deleteAdminGameOpponentStat($id);

        if ($result['success']) {
            $this->Flash->success(__('The opponent player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The opponent player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $result['gameId']]);
    }
}
