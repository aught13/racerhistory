<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteAdminService;
use Cake\Http\Response;

/**
 * Sites Admin Controller
 *
 * Human-focused summary:
 * Handles site administration endpoints and delegates all data/persistence
 * orchestration to SiteAdminService.
 *
 * Agent-focused maintenance notes:
 * - Keep HTTP concerns (allowMethod, flash, redirects) in this controller.
 * - Preserve JSON payload shape for popup and search integrations.
 * - Keep place-filter semantics in ajaxSearch unchanged.
 *
 * @property \App\Service\SiteAdminService $siteAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\SitesTable $Sites
 */
class SitesController extends AppController
{
    /**
     * Service that owns site admin orchestration.
     *
     * @var \App\Service\SiteAdminService
     */
    protected SiteAdminService $siteAdminService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->siteAdminService = new SiteAdminService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig('unlockedActions', array_merge($current, ['ajaxSearch', 'ajaxAdd']));
        }
    }

    /**
     * List sites.
     */
    public function index(): void
    {
        $this->set('siteCount', $this->siteAdminService->getTotalCount());
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

        $result = $this->siteAdminService->buildDataTablesResponse([
            'draw' => (int)$this->request->getQuery('draw'),
            'start' => (int)$this->request->getQuery('start'),
            'length' => (int)$this->request->getQuery('length'),
            'searchValue' => trim((string)($this->request->getQuery('search')['value'] ?? '')),
            'orderColumn' => $orderColumn,
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
     * Add a new site.
     */
    public function add(): ?Response
    {
        $viewData = $this->siteAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->siteAdminService->saveNewSite((array)$this->request->getData());
            $viewData['site'] = $result['site'];

            if ($result['success']) {
                $this->Flash->success('The site has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The site could not be saved.');
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Edit a site.
     *
     * @param string $id
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->siteAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->siteAdminService->saveExistingSite($id, (array)$this->request->getData());
            $viewData['site'] = $result['site'];

            if ($result['success']) {
                $this->Flash->success('The site has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The site could not be saved.');
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Delete a site.
     *
     * @param string $id
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->siteAdminService->deleteSite($id)) {
            $this->Flash->success('The site has been deleted.');
        } else {
            $this->Flash->error('The site could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search sites.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $placeId = $this->request->getQuery('place_id') !== null
            ? (int)$this->request->getQuery('place_id')
            : null;
        $payload = $this->siteAdminService->buildSearchResponse(
            (string)$this->request->getQuery('q'),
            $placeId,
            30,
        );

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX add site from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $response = $this->siteAdminService->createSiteFromPopup((array)$this->request->getData());

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
