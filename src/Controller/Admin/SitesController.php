<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteService;
use Cake\Http\Response;

/**
 * Admin Sites Controller
 *
 * Provides CRUD operations for managing sites in the admin interface. The index action lists all sites, while the add and edit actions allow for creating and updating sites, respectively. The delete action handles site deletion. The controller also includes AJAX actions for searching sites and adding new sites from a popup form, returning JSON responses for seamless integration with the frontend.
 *
 * Actions:
 * - index: Lists all sites with their associated places.
 * - add: Handles the creation of a new site, including form display and processing.
 * - edit: Handles the editing of an existing site, including form display and processing.
 * - delete: Handles the deletion of a site, ensuring that the request method is POST or DELETE to prevent accidental deletions via GET requests.
 * - ajaxSearch: Provides an endpoint for searching sites based on a query string, returning results in JSON format for use in autocomplete fields or similar UI components.
 * - ajaxAdd: Provides an endpoint for adding a new site from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new sites without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage sites. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - SiteService: Provides methods for searching sites and getting display labels, abstracting away the details of these operations from the controller.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * Note: The AJAX actions (ajaxSearch and ajaxAdd) are designed to be used with JavaScript on the frontend to provide dynamic search and add functionality without requiring full page reloads. They return JSON responses that indicate success or failure and include any relevant data or error messages.
 *
 * @property \App\Service\SiteService $siteService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class SitesController extends AppController
{
    /**
     * Initialize controller.
     */
    public function initialize(): void
    {
        parent::initialize();

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
        $sites = $this->fetchTable('Sites')->find()->contain(['Places'])->all();
        $this->set(compact('sites'));
    }

    /**
     * Add a new site.
     */
    public function add(): ?Response
    {
        $sites = $this->fetchTable('Sites');
        $site = $sites->newEmptyEntity();
        if ($this->request->is('post')) {
            $site = $sites->patchEntity($site, $this->request->getData());
            if ($sites->save($site)) {
                $this->Flash->success('The site has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The site could not be saved.');
        }
        $places = $this->fetchTable('Places')->find('list')->all();
        $this->set(compact('site', 'places'));

        return null;
    }

    /**
     * Edit a site.
     */
    public function edit(string $id): ?Response
    {
        $sites = $this->fetchTable('Sites');
        $site = $sites->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $site = $sites->patchEntity($site, $this->request->getData());
            if ($sites->save($site)) {
                $this->Flash->success('The site has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The site could not be saved.');
        }
        $places = $this->fetchTable('Places')->find('list')->all();
        $this->set(compact('site', 'places'));

        return null;
    }

    /**
     * Delete a site.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $sites = $this->fetchTable('Sites');
        $entity = $sites->get($id);
        if ($sites->delete($entity)) {
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
        $q = trim((string)$this->request->getQuery('q'));
        $placeId = $this->request->getQuery('place_id');
        $service = new SiteService();

        if ($q === '') {
            $results = [];
        } else {
            $sites = $service->searchSites($q, 30);
            $results = [];
            foreach ($sites as $site) {
                if ($placeId && (int)$site->place_id !== (int)$placeId) {
                    continue;
                }
                $placeName = $site->place->place_city ?? '';
                $placeState = $site->place->place_state ?? '';
                $results[] = [
                    'id' => $site->id,
                    'site_name' => $site->site_name,
                    'capacity' => $site->capacity,
                    'place_city' => $placeName,
                    'place_state' => $placeState,
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'results' => $results]));
    }

    /**
     * AJAX add site from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $sites = $this->fetchTable('Sites');
        $site = $sites->newEmptyEntity();

        if ($this->request->is('post')) {
            $site = $sites->patchEntity($site, $this->request->getData());
            if ($sites->save($site)) {
                $displayLabel = (new SiteService())->getDisplayLabel((int)$site->id);

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'The site has been saved.',
                        'newOption' => [
                            'value' => $site->id,
                            'text' => $displayLabel,
                        ],
                    ]));
            }

            $errors = [];
            foreach ($site->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = ucfirst($field) . ': ' . $error;
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save site.'],
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => ['Invalid request method.'],
            ]));
    }
}
