<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin Sports Controller
 *
 * Handles administrative sports management operations.
 * Provides functionality for sports administration and CRUD operations.
 *
 * @property \App\Model\Table\SportsTable $Sports
 */
class SportsController extends AppController
{
    /**
     * List all sports for administration.
     *
     * @return void
     */
    public function index()
    {
        $sports = $this->Sports->find()->all();
        $this->set(compact('sports'));
    }

    /**
     * View a single sport.
     *
     * @param string $id Sport ID
     * @return void
     */
    public function view(string $id)
    {
        $sport = $this->Sports->get($id);
        $this->set(compact('sport'));
    }

    /**
     * Add new sport form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        $sport = $this->Sports->newEmptyEntity();

        if ($this->request->is('post')) {
            $sport = $this->Sports->patchEntity($sport, $this->request->getData());

            if ($this->Sports->save($sport)) {
                $this->Flash->success(__('The sport has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sport could not be saved. Please, try again.'));
        }

        $this->set(compact('sport'));

        return null;
    }

    /**
     * Edit sport form and processing.
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response|null
     */
    public function edit(string $id)
    {
        $sport = $this->Sports->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $sport = $this->Sports->patchEntity($sport, $this->request->getData());

            if ($this->Sports->save($sport)) {
                $this->Flash->success(__('The sport has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sport could not be saved. Please, try again.'));
        }

        $this->set(compact('sport'));

        return null;
    }

    /**
     * Delete a sport.
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id)
    {
        $this->request->allowMethod(['post', 'delete']);
        $sport = $this->Sports->get($id);

        if ($this->Sports->delete($sport)) {
            $this->Flash->success(__('The sport has been deleted.'));
        } else {
            $this->Flash->error(__('The sport could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete multiple sports.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete()
    {
        $this->request->allowMethod(['post']);
        $sportIds = $this->request->getData('sport_ids');

        if (empty($sportIds)) {
            $this->Flash->error('No sports selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = 0;
        foreach ($sportIds as $id) {
            $sport = $this->Sports->get($id);
            if ($this->Sports->delete($sport)) {
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->Flash->success(__('Deleted {0} sport(s).', $deletedCount));
        } else {
            $this->Flash->error('No sports could be deleted.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher for sports.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'delete') {
            return $this->bulkDelete();
        }

        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }
}
