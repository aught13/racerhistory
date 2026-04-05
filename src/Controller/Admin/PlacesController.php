<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PlaceService;
use Cake\Http\Response;

class PlacesController extends AppController
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
     * List places.
     */
    public function index(): void
    {
        $places = $this->fetchTable('Places')->find()->all();
        $this->set(compact('places'));
    }

    /**
     * Add a new place.
     */
    public function add(): ?Response
    {
        $places = $this->fetchTable('Places');
        $place = $places->newEmptyEntity();
        if ($this->request->is('post')) {
            $place = $places->patchEntity($place, $this->request->getData());
            if ($places->save($place)) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            // Surface duplicate error clearly
            $errors = $place->getErrors();
            if (isset($errors['place_country']['_isUnique']) || isset($errors['place_city']['_isUnique'])) {
                $this->Flash->error('A place with that country, city, and state already exists.');
            } else {
                $this->Flash->error('The place could not be saved.');
            }
        }
        $this->set(compact('place'));

        return null;
    }

    /**
     * Edit a place.
     */
    public function edit(string $id): ?Response
    {
        $places = $this->fetchTable('Places');
        $place = $places->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $place = $places->patchEntity($place, $this->request->getData());
            if ($places->save($place)) {
                $this->Flash->success('The place has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The place could not be saved.');
        }

        // Manage sites for this place
        $sitesTable = $this->fetchTable('Sites');
        $sites = $sitesTable->find()->where(['place_id' => $id])->all();
        $newSite = $sitesTable->newEmptyEntity();
        $this->set(compact('place', 'sites', 'newSite'));

        return null;
    }

    /**
     * Delete a place.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $places = $this->fetchTable('Places');
        $entity = $places->get($id);
        if ($places->delete($entity)) {
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
        $q = trim((string)$this->request->getQuery('q'));
        $service = new PlaceService();

        if ($q === '') {
            $results = [];
        } else {
            $places = $service->searchPlaces($q, 30);
            $results = [];
            foreach ($places as $place) {
                $results[] = [
                    'id' => $place->id,
                    'place_country' => $place->place_country,
                    'place_city' => $place->place_city,
                    'place_state' => $place->place_state,
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'results' => $results]));
    }

    /**
     * AJAX add place from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $places = $this->fetchTable('Places');

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Check for existing duplicate first
            $conditions = [
                'place_country' => $data['place_country'] ?? '',
                'place_city' => $data['place_city'] ?? '',
                'place_state' => $data['place_state'] ?? '',
            ];
            $existing = $places->find()->where($conditions)->first();
            if ($existing) {
                $label = $existing->place_city;
                if (!empty($existing->place_state)) {
                    $label .= ', ' . $existing->place_state;
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'Place already exists — selected automatically.',
                        'newOption' => [
                            'value' => $existing->id,
                            'text' => $label,
                        ],
                        'place' => [
                            'id' => $existing->id,
                            'place_country' => $existing->place_country,
                            'place_city' => $existing->place_city,
                            'place_state' => $existing->place_state,
                        ],
                    ]));
            }

            $place = $places->newEmptyEntity();
            $place = $places->patchEntity($place, $data);
            if ($places->save($place)) {
                $label = $place->place_city;
                if (!empty($place->place_state)) {
                    $label .= ', ' . $place->place_state;
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'The place has been saved.',
                        'newOption' => [
                            'value' => $place->id,
                            'text' => $label,
                        ],
                        'place' => [
                            'id' => $place->id,
                            'place_country' => $place->place_country,
                            'place_city' => $place->place_city,
                            'place_state' => $place->place_state,
                        ],
                    ]));
            }

            $errors = [];
            foreach ($place->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = ucfirst($field) . ': ' . $error;
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save place.'],
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
