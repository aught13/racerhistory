<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

class PlacesController extends AppController
{
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
            $this->Flash->error('The place could not be saved.');
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
}
