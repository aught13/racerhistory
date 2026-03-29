<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteService;
use Cake\Http\Response;

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
                $placeName = $site->place->place_name ?? '';
                $placeState = $site->place->place_state ?? '';
                $results[] = [
                    'id' => $site->id,
                    'site_name' => $site->site_name,
                    'capacity' => $site->capacity,
                    'place_name' => $placeName,
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
