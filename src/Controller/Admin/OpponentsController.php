<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

class OpponentsController extends AppController
{
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
        $places = $this->fetchTable('Places')->find('list')->all();
        $this->set(compact('opponent', 'places'));

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
        $places = $this->fetchTable('Places')->find('list')->all();
        $this->set(compact('opponent', 'places'));

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
}
