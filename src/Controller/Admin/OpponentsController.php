<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\OpponentAdminService;
use Cake\Http\Response;

/**
 * Opponents Admin Controller
 *
 * Handles opponent administration endpoints and delegates all query and
 * persistence orchestration to OpponentAdminService.
 *
 * Notes:
 * - Keep HTTP concerns (allowMethod, flash, redirects) in this controller.
 * - Add or change admin behavior in OpponentAdminService first.
 * - Preserve JSON response payload keys used by popup/autocomplete UI.
 *
 * @property \App\Service\OpponentAdminService $opponentAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\OpponentsTable $Opponents
 */
class OpponentsController extends AppController
{
    /**
     * Service that owns opponent admin orchestration.
     *
     * @var \App\Service\OpponentAdminService
     */
    protected OpponentAdminService $opponentAdminService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->opponentAdminService = new OpponentAdminService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig('unlockedActions', array_merge($current, ['ajaxSearch', 'ajaxAdd']));
        }
    }

    /**
     * List opponents.
     */
    public function index(): void
    {
        $this->set('opponentCount', $this->opponentAdminService->getTotalCount());
    }

    /**
     * DataTables server-side JSON endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function datatables(): Response
    {
        $this->request->allowMethod(['get']);

        $orderColumn = 0;
        $orderDir = 'asc';
        $order = $this->request->getQuery('order');
        if (is_array($order) && !empty($order)) {
            $firstOrder = reset($order);
            if (is_array($firstOrder)) {
                $orderColumn = (int)($firstOrder['column'] ?? 0);
                $dir = strtolower((string)($firstOrder['dir'] ?? 'asc'));
                if (in_array($dir, ['asc', 'desc'], true)) {
                    $orderDir = $dir;
                }
            }
        }

        $result = $this->opponentAdminService->buildDataTablesResponse([
            'draw' => (int)$this->request->getQuery('draw'),
            'start' => (int)$this->request->getQuery('start'),
            'length' => (int)$this->request->getQuery('length'),
            'searchValue' => trim((string)($this->request->getQuery('search')['value'] ?? '')),
            'orderColumn' => $orderColumn,
            'orderDir' => $orderDir,
        ], $this->request->getAttribute('identity'));

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'draw' => $result['draw'],
                'recordsTotal' => $result['total'],
                'recordsFiltered' => $result['filtered'],
                'data' => $result['data'],
            ]));
    }

    /**
     * Add a new opponent.
     */
    public function add(): ?Response
    {
        $viewData = $this->opponentAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->opponentAdminService->saveNewOpponent((array)$this->request->getData());
            $viewData['opponent'] = $result['opponent'];

            if ($result['success']) {
                $this->Flash->success('The opponent has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The opponent could not be saved.');
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Edit an opponent.
     *
     * @param string $id
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->opponentAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->opponentAdminService->saveExistingOpponent($id, (array)$this->request->getData());
            $viewData['opponent'] = $result['opponent'];

            if ($result['success']) {
                $this->Flash->success('The opponent has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The opponent could not be saved.');
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Delete an opponent.
     *
     * @param string $id
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $identity = $this->request->getAttribute('identity');

        if ($this->opponentAdminService->deleteOpponent($id, $identity)) {
            $this->Flash->success('The opponent has been deleted.');
        } else {
            $this->Flash->error('The opponent could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search opponents.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $payload = $this->opponentAdminService->buildSearchResponse((string)$this->request->getQuery('q'), 30);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX add opponent from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $response = $this->opponentAdminService->createOpponentFromPopup((array)$this->request->getData());

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($response));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => ['Invalid request method.'],
            ]));
    }
}
