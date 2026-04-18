<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\GameTypeService;
use Cake\Http\Response;

class GameTypesController extends AppController
{
    /**
     * Initialize controller and adjust FormProtection unlocked actions.
     */
    public function initialize(): void
    {
        parent::initialize();

        if ($this->components()->has('FormProtection')) {
            $current = (array)$this->FormProtection->getConfig('unlockedActions');
            $unlocked = array_merge($current, ['delete', 'ajaxSearch', 'ajaxAdd']);
            $this->FormProtection->setConfig('unlockedActions', $unlocked);
        }
    }

    /**
     * List game types.
     */
    public function index(): void
    {
        $gameTypes = $this->fetchTable('GameTypes')->find()->all();
        $this->set(compact('gameTypes'));
    }

    /**
     * Add a new game type.
     */
    public function add(): ?Response
    {
        $table = $this->fetchTable('GameTypes');
        $gameType = $table->newEmptyEntity();
        if ($this->request->is('post')) {
            $gameType = $table->patchEntity($gameType, $this->request->getData());
            if ($table->save($gameType)) {
                $this->Flash->success('The game type has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The game type could not be saved.');
        }
        $this->set(compact('gameType'));

        return null;
    }

    /**
     * Edit a game type.
     */
    public function edit(string $id): ?Response
    {
        $table = $this->fetchTable('GameTypes');
        $gameType = $table->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $gameType = $table->patchEntity($gameType, $this->request->getData());
            if ($table->save($gameType)) {
                $this->Flash->success('The game type has been saved.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('The game type could not be saved.');
        }
        $this->set(compact('gameType'));

        return null;
    }

    /**
     * Delete a game type.
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('GameTypes');
        $entity = $table->get($id);
        if ($table->Games->exists(['game_type_id' => $entity->id])) {
            $this->Flash->error('This game type cannot be deleted because games are associated with it.');

            return $this->redirect(['action' => 'index']);
        }

        if ($table->delete($entity)) {
            $this->Flash->success('The game type has been deleted.');
        } else {
            $this->Flash->error('The game type could not be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * AJAX search game types.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxSearch(): Response
    {
        $this->request->allowMethod(['get']);
        $q = trim((string)$this->request->getQuery('q'));
        $service = new GameTypeService();

        if ($q === '') {
            $results = [];
        } else {
            $gameTypes = $service->searchGameTypes($q, 30);
            $results = [];
            foreach ($gameTypes as $gt) {
                $results[] = [
                    'id' => $gt->id,
                    'game_type_name' => $gt->game_type_name,
                    'abr' => $gt->abr,
                    'post' => $gt->post,
                    'conf' => $gt->conf,
                ];
            }
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['success' => true, 'results' => $results]));
    }

    /**
     * AJAX add game type from popup form.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $table = $this->fetchTable('GameTypes');
        $gameType = $table->newEmptyEntity();

        if ($this->request->is('post')) {
            $gameType = $table->patchEntity($gameType, $this->request->getData());
            if ($table->save($gameType)) {
                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => true,
                        'message' => 'The game type has been saved.',
                        'newOption' => [
                            'value' => $gameType->id,
                            'text' => $gameType->game_type_name,
                        ],
                    ]));
            }

            $errors = [];
            foreach ($gameType->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = ucfirst($field) . ': ' . $error;
                }
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save game type.'],
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
