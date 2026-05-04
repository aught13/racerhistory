<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PersonAdminService;
use Cake\Http\Response;

/**
 * Admin Persons Controller
 *
 * Thin HTTP orchestrator for managing Person records. All ORM access and
 * business logic live in PersonAdminService; this controller extracts request
 * data, delegates, then sets flash messages, builds JSON responses, and
 * redirects.
 *
 * Actions:
 * - index: Renders the shell page; total count injected for the heading label.
 *   Actual rows are fetched client-side by datatables.
 * - datatables: JSON endpoint for DataTables server-side processing (pagination,
 *   search, sort, action HTML).
 * - view: Person detail with sport-grouped rosters and career stats.
 * - add: Add form and POST handler.
 * - edit: Edit form and POST handler.
 * - delete: POST/DELETE handler for a single person.
 * - bulkDelete: POST handler for multiple person deletions.
 * - bulk: Dispatcher that routes bulk_action to bulkDelete.
 * - ajaxAdd: JSON endpoint for popup-form person creation.
 * - ajaxSearch: JSON endpoint for name-based autocomplete.
 *
 * Notes:
 * - Do not put ORM queries here; that belongs in PersonAdminService.
 * - Flash strings and JSON shapes are asserted in tests — keep them stable.
 * - DataTables response keys (draw, recordsTotal, recordsFiltered, data) must
 *   remain exactly as-is for the frontend DataTables integration.
 *
 * @property \App\Service\PersonAdminService $personAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\PersonsTable $Persons
 */
class PersonsController extends AppController
{
    /**
     * @var \App\Service\PersonAdminService Admin service for persons CRUD and search
     */
    protected PersonAdminService $personAdminService;

    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->personAdminService = new PersonAdminService();
    }

    /**
     * Index: list persons shell (data loaded via datatables action).
     *
     * @return void
     */
    public function index(): void
    {
        $this->set('personCount', $this->personAdminService->getTotalCount());
    }

    /**
     * DataTables server-side JSON endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function datatables(): Response
    {
        $this->request->allowMethod(['get']);

        $orderDir = 'asc';
        $order = $this->request->getQuery('order');
        if (is_array($order) && !empty($order)) {
            $firstOrder = reset($order);
            if (is_array($firstOrder)) {
                $dir = strtolower((string)($firstOrder['dir'] ?? 'asc'));
                if (in_array($dir, ['asc', 'desc'], true)) {
                    $orderDir = $dir;
                }
            }
        }

        $result = $this->personAdminService->buildDataTablesResponse([
            'draw' => (int)$this->request->getQuery('draw'),
            'start' => (int)$this->request->getQuery('start'),
            'length' => (int)$this->request->getQuery('length'),
            'searchValue' => trim((string)($this->request->getQuery('search')['value'] ?? '')),
            'orderDir' => $orderDir,
        ]);

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
     * View a single person with roster entries and career stats.
     *
     * @param string $id Person id
     * @return void
     */
    public function view(string $id): void
    {
        $this->set($this->personAdminService->getViewData($id));
    }

    /**
     * Add person form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        if ($this->request->is('post')) {
            $result = $this->personAdminService->add($this->request->getData());

            if ($result['success']) {
                $this->Flash->success(__('The person has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The person could not be saved. Please, try again.'));
            $this->set('person', $result['person']);

            return null;
        }

        /** @var \App\Model\Entity\Person $person */
        $person = $this->fetchTable('Persons')->newEmptyEntity();
        $this->set(compact('person'));

        return null;
    }

    /**
     * Edit person form and processing.
     *
     * @param string $id Person id
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id): ?Response
    {
        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->personAdminService->edit($id, $this->request->getData());

            if ($result['success']) {
                $this->Flash->success(__('The person has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The person could not be saved. Please, try again.'));
            $this->set('person', $result['person']);

            return null;
        }

        $this->set('person', $this->personAdminService->getEditEntity($id));

        return null;
    }

    /**
     * Delete a person.
     *
     * @param string $id Person id
     * @return \Cake\Http\Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->personAdminService->delete($id)) {
            $this->Flash->success(__('The person has been deleted.'));
        } else {
            $this->Flash->error(__('The person could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete persons.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete(): Response
    {
        $this->request->allowMethod(['post']);
        $ids = (array)$this->request->getData('person_ids');
        $ids = array_values(array_filter($ids, fn($v) => $v !== '' && $v !== null && ctype_digit((string)$v)));

        if (empty($ids)) {
            $this->Flash->error('No persons selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deleted = $this->personAdminService->bulkDelete($ids);

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} person(s).', $deleted));
        } else {
            $this->Flash->error('No persons could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk dispatcher.
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
     * AJAX add (popup form) endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $result = $this->personAdminService->createPersonFromPopup($this->request->getData());

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($result));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => ['Invalid request method.'],
            ]));
    }

    /**
     * AJAX search persons for dynamic select (debounced client queries).
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));
        $result = $this->personAdminService->buildAjaxSearchResponse($q);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }
}
