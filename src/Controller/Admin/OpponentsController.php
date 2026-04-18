<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\OpponentService;
use App\Service\PlaceService;
use Cake\Http\Response;

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
