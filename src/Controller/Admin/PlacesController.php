<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PlaceService;
use Cake\Http\Response;

/**
 * Admin Places Controller
 *
 * Provides CRUD operations for managing places in the admin interface. The index action lists all places, while the add and edit actions allow for creating and updating places, respectively. The delete action handles place deletion. The controller also includes AJAX actions for searching places and adding new places from a popup form, returning JSON responses for seamless integration with the frontend.
 *
 * Actions:
 * - index: Lists all places with their associated sites.
 * - add: Handles the creation of a new place, including form display and processing.
 * - edit: Handles the editing of an existing place, including form display and processing.
 * - delete: Handles the deletion of a place, ensuring that the request method is POST or DELETE to prevent accidental deletions via GET requests.
 * - ajaxSearch: Provides an endpoint for searching places based on a query string, returning results in JSON format for use in autocomplete fields or similar UI components.
 * - ajaxAdd: Provides an endpoint for adding a new place from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new places without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage places. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - PlaceService: Provides methods for searching places, abstracting away the details of these operations from the controller.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * @property \App\Service\PlaceService $placeService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\PlacesTable $Places
 * @property \App\Model\Table\SitesTable $Sites
 * @property \App\Service\PlaceService $placeService
 * @property \App\Service\SiteService $siteService
 * @property \App\Service\SeasonService $seasonService
 * @property \App\Service\TeamSeasonService $teamSeasonService
 * @property \App\Service\GameService $gameService
 * @property \App\Service\GameTypeService $gameTypeService
 * @property \App\Service\OpponentService $opponentService
 */
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
