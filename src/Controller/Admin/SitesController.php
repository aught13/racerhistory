<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

class SitesController extends AppController
{
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
}
