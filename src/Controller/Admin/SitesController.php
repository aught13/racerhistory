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
        $this->set($this->siteAdminService->getIndexData());
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
            30
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
