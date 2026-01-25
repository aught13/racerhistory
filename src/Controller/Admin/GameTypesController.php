<?php
declare(strict_types=1);

namespace App\Controller\Admin;

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
            $current[] = 'delete';
            $this->FormProtection->setConfig('unlockedActions', $current);
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
}
