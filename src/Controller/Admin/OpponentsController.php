<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\OpponentService;
use App\Service\PlaceService;
use Cake\Http\Response;

/**
 * Admin Opponents Controller
 *
 * Provides CRUD operations for managing opponents in the admin interface. The index action lists all opponents, while the add and edit actions allow for creating and updating opponents, respectively. The delete action handles opponent deletion. The controller also includes AJAX actions for searching opponents and adding new opponents from a popup form, returning JSON responses for seamless integration with the frontend.
 *
 * Actions:
 * - index: Lists all opponents with their associated places.
 * - add: Handles the creation of a new opponent, including form display and processing.
 * - edit: Handles the editing of an existing opponent, including form display and processing.
 * - delete: Handles the deletion of an opponent, ensuring that the request method is POST or DELETE to prevent accidental deletions via GET requests.
 * - ajaxSearch: Provides an endpoint for searching opponents based on a query string, returning results in JSON format for use in autocomplete fields or similar UI components.
 * - ajaxAdd: Provides an endpoint for adding a new opponent from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new opponents without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage opponents. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - OpponentService: Provides methods for searching opponents, abstracting away the details of these operations from the controller.
 * - PlaceService: Used to retrieve a list of places for selection in the opponent form.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * @property \App\Service\OpponentService $opponentService
 * @property \App\Service\PlaceService $placeService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Model\Table\OpponentsTable $Opponents
 */
class OpponentsController extends AppController
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
     * List opponents.
     */
    public function index(): void
    {
        $opponents = $this->fetchTable('Opponents')->find()->contain(['Places'])->all();
        $this->set(compact('opponents'));
    }

    /**
     * Add a new opponent.
     */
    public function add(): ?Response
    {
        $opponents = $this->fetchTable('Opponents');
        $opponent = $opponents->newEmptyEntity();
        if ($this->request->is('post')) {
            $opponent = $opponents->patchEntity($opponent, $this->request->getData());
            if ($opponents->save($opponent)) {
                $this->Flash->success('The opponent has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The opponent could not be saved.');
        }

        $places = (new PlaceService())->getPlacesList();

        // Get all opponents for opponent_current dropdown (sorted by name)
        $opponentsList = $this->fetchTable('Opponents')->find('list')
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        $this->set(compact('opponent', 'places', 'opponentsList'));

        return null;
    }

    /**
     * Edit an opponent.
     */
    public function edit(string $id): ?Response
    {
        $opponents = $this->fetchTable('Opponents');
        $opponent = $opponents->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $opponent = $opponents->patchEntity($opponent, $this->request->getData());
            if ($opponents->save($opponent)) {
                $this->Flash->success('The opponent has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The opponent could not be saved.');
        }

        $places = (new PlaceService())->getPlacesList();

        // Get all opponents except current one for opponent_current dropdown
        $opponentsList = $this->fetchTable('Opponents')->find('list')
            ->where(['Opponents.id !=' => $id])
            ->orderBy(['Opponents.opponent_name' => 'ASC'])
            ->all();

        $this->set(compact('opponent', 'places', 'opponentsList'));

        return null;
    }

    /**
     * Delete an opponent.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $opponents = $this->fetchTable('Opponents');
        $entity = $opponents->get($id);
        if ($opponents->delete($entity)) {
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
        $q = trim((string)$this->request->getQuery('q'));
        $service = new OpponentService();

        if ($q === '') {
            $results = [];
        } else {
            $opponents = $service->searchOpponents($q, 30);
            $results = [];
            foreach ($opponents as $opp) {
                $results[] = [
                    'id' => $opp->id,
                    'opponent_name' => $opp->opponent_name,
                    'opponent_short' => $opp->opponent_short,
                    'opponent_abbr' => $opp->opponent_abbr,
                    'opponent_mascot' => $opp->opponent_mascot,
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'results' => $results]));
    }

    /**
     * AJAX add opponent from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $opponents = $this->fetchTable('Opponents');
        $opponent = $opponents->newEmptyEntity();

        if ($this->request->is('post')) {
            $opponent = $opponents->patchEntity($opponent, $this->request->getData());
            if ($opponents->save($opponent)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'The opponent has been saved.',
                        'newOption' => [
                            'value' => $opponent->id,
                            'text' => $opponent->opponent_name,
                        ],
                    ]));
            }

            $errors = [];
            foreach ($opponent->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = ucfirst($field) . ': ' . $error;
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save opponent.'],
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
