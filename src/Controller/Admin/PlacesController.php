<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PlaceAdminService;
use Cake\Http\Response;

/**
 * Places Admin Controller
 *
 * Handles place administration endpoints and delegates all data/persistence
 * orchestration to PlaceAdminService.
 *
 * Notes:
 * - Keep HTTP-only concerns (allowMethod, flash, redirects) in this class.
 * - Keep duplicate-place semantics consistent for both HTML and popup flows.
 * - Preserve JSON response keys used by frontend popup integrations.
 *
 * @property \App\Service\PlaceAdminService $placeAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\PlacesTable $Places
 * @property \App\Model\Table\SitesTable $Sites
 */
class PlacesController extends AppController
{
    /**
     * Service that owns place admin orchestration.
     *
     * @var \App\Service\PlaceAdminService
     */
    protected PlaceAdminService $placeAdminService;

    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->placeAdminService = new PlaceAdminService();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $this->FormProtection->setConfig('unlockedActions', array_merge($current, ['ajaxSearch', 'ajaxAdd']));
        }
    }

    /**
     * List places.
     */
    public function index(): void
    {
        $this->set($this->placeAdminService->getIndexData());
    }

    /**
     * Add a new place.
     */
    public function add(): ?Response
    {
        $viewData = $this->placeAdminService->getAddFormData();

        if ($this->request->is('post')) {
            $result = $this->placeAdminService->saveNewPlace((array)$this->request->getData());
            $viewData['place'] = $result['place'];

            if ($result['success']) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }

            if ($result['duplicateViolation']) {
                $this->Flash->error('A place with that country, city, and state already exists.');
            } else {
                $this->Flash->error('The place could not be saved.');
            }
        }
        $this->set($viewData);

        return null;
    }

    /**
     * Edit a place.
     */
    public function edit(string $id): ?Response
    {
        $viewData = $this->placeAdminService->getEditFormData($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->placeAdminService->saveExistingPlace($id, (array)$this->request->getData());
            $viewData['place'] = $result['place'];

            if ($result['success']) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }

            if ($result['duplicateViolation']) {
                $this->Flash->error('A place with that country, city, and state already exists.');
            } else {
                $this->Flash->error('The place could not be saved.');
            }
        }

        $this->set($viewData);

        return null;
    }

    /**
     * Delete a place.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        if ($this->placeAdminService->deletePlace($id)) {
            $this->Flash->success('The place has been deleted.');
        } else {
            $this->Flash->error('The place could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search places.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $payload = $this->placeAdminService->buildSearchResponse((string)$this->request->getQuery('q'), 30);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    /**
     * AJAX add place from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        if ($this->request->is('post')) {
            $response = $this->placeAdminService->createPlaceFromPopup((array)$this->request->getData());

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
