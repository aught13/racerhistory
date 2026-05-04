<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\BasketballStatsAdminService;
use Cake\Http\Response;

/**
 * Admin controller for basketball player game stat workflows.
 *
 * This controller stays thin: it handles request methods, flash messaging,
 * and redirects while delegating stat loading, bulk add/edit/delete flows,
 * roster option building, and season-total reconciliation to
 * BasketballStatsAdminService.
 *
 * @property \App\Model\Table\StatBasketGamePersonTable $StatBasketGamePerson
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class StatBasketGamePersonController extends AppController
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
                array_merge($current, ['bulkAdd', 'delete', 'deleteConfirm']),
            );
        }
    }

    /**
     * View method - display stats for a specific game
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function view(int $gameId)
    {
        $this->set($this->basketballStatsAdminService->getAdminGamePersonViewData($gameId));
    }

    /**
     * Add method - displays multi-row form for adding player stats.
     *
     * GET renders the bulk add form with one empty row. Players who already have
     * stats for this game are excluded from the roster dropdown.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function add(int $gameId)
    {
        $this->set($this->basketballStatsAdminService->getAdminGamePersonAddData($gameId));
    }

    /**
     * Bulk add multiple player stat entries at once.
     *
     * Accepts an array of stat row data and saves each as a new entity.
     * Optionally adds each stat to season totals when the checkbox is checked.
     *
     * @param int $gameId Game ID
     * @return \Cake\Http\Response|null
     */
    public function bulkAdd(int $gameId): ?Response
    {
        $this->request->allowMethod(['post']);

        $rows = (array)$this->request->getData('rows');
        $addToTotals = (bool)$this->request->getData('add_to_totals');

        if (empty($rows)) {
            $this->Flash->error(__('No player stats to save.'));

            return $this->redirect(['action' => 'add', $gameId]);
        }

        $result = $this->basketballStatsAdminService->saveAdminGamePersonRows($gameId, $rows, $addToTotals);
        $saved = $result['saved'];
        $skipped = $result['skipped'];
        $errors = $result['errors'];
        $failedRows = $result['failedRows'];

        if ($saved > 0) {
            $this->Flash->success(__('Saved {0} player stat(s).', $saved));
        }
        if ($skipped > 0) {
            $msg = __('Skipped {0} player(s) that already have stats for this game.', $skipped);
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
            $this->set($this->basketballStatsAdminService->getAdminGamePersonAddData($gameId) + compact('failedRows'));

            return $this->render('add');
        }

        return $this->redirect(['controller' => 'Games', 'action' => 'view', $gameId]);
    }

    /**
     * Edit method - update existing player stat entry
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function edit(int $id)
    {
        $viewData = $this->basketballStatsAdminService->getAdminGamePersonEditData($id);
        $stat = $viewData['stat'];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->basketballStatsAdminService->updateAdminGamePersonStat(
                $id,
                (array)$this->request->getData(),
                (bool)$this->request->getData('add_to_totals'),
            );
            $stat = $result['stat'];
            if ($result['success']) {
                $this->Flash->success(__('The player stat has been saved.'));

                return $this->redirect(['action' => 'view', $stat->game_id]);
            }
            $this->Flash->error(__('The player stat could not be saved. Please, try again.'));
        }

        $this->set($viewData + ['stat' => $stat]);
    }

    /**
     * Delete confirm method - renders a confirmation page before deleting.
     *
     * Prompts the user to confirm the deletion and optionally deduct the
     * stat from season totals.
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null|void Renders view.
     */
    public function deleteConfirm(int $id)
    {
        $this->set($this->basketballStatsAdminService->getAdminGamePersonDeleteData($id));
    }

    /**
     * Delete method - remove player stat entry
     *
     * Accepts an optional `deduct_from_totals` POST param. When set and the
     * stat has period 'Z', the values are subtracted from the player's season
     * totals before deletion.
     *
     * @param int $id Stat ID
     * @return \Cake\Http\Response|null Redirects to view
     */
    public function delete(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $deductFromTotals = (bool)$this->request->getData('deduct_from_totals');
        $result = $this->basketballStatsAdminService->deleteAdminGamePersonStat($id, $deductFromTotals);

        if ($result['success']) {
            $this->Flash->success(__('The player stat has been deleted.'));
        } else {
            $this->Flash->error(__('The player stat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $result['gameId']]);
    }
}
