<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SportConfigAdminService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Sports Controller
 *
 * Handles administrative sports management operations.
 * Provides functionality for sports administration and CRUD operations.
 *
 * Sports are the foundation of the application's historical sports information and statistics, representing different
 * types of competitive activities (e.g., Basketball, Football, Soccer).
 * Each sport can have multiple teams associated with it.
 *
 * @property \App\Model\Table\SportsTable $Sports
 */
class SportsController extends AppController
{
    private SportConfigAdminService $sportConfigAdminService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->sportConfigAdminService = new SportConfigAdminService();
    }

    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event An Event instance
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Disable form protection for editConfigs due to dynamic form fields
        if ($this->request->getParam('action') === 'editConfigs') {
            if ($this->components()->has('FormProtection')) {
                $this->FormProtection->setConfig('unlockedActions', ['editConfigs']);
            }
        }
    }

    /**
     * List all sports for administration.
     *
     * @return void
     */
    public function index()
    {
        // Include Teams so templates can surface associated record counts in delete confirmations
        $sports = $this->Sports->find()->contain(['Teams'])->all();

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
        $sport = $this->Sports->get($id, contain: ['Teams']);

        $configs = $this->sportConfigAdminService->getFormattedConfigsForSport((int)$id);

        $this->set(compact('sport', 'configs'));
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
        // Contain Teams so we can show associated record counts in the confirmation modal
        $sport = $this->Sports->get($id, contain: ['Teams']);

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
        $sportIds = (array)$this->request->getData('sport_ids');
        $sportIds = array_values(array_filter($sportIds, function ($v) {
            return $v !== '' && $v !== null && ctype_digit((string)$v);
        }));

        if (empty($sportIds)) {
            $this->Flash->error('No sports selected for deletion.');

            return $this->redirect(['action' => 'index']);
        }

        $deletedCount = 0;
        foreach ($sportIds as $id) {
            try {
                $sport = $this->Sports->get($id);

                if ($this->Sports->delete($sport)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $e) {
                continue;
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

    /**
     * AJAX endpoint for adding sports from popup forms.
     *
     * @return \Cake\Http\Response
     */
    public function ajaxAdd(): Response
    {
        $sport = $this->Sports->newEmptyEntity();

        if ($this->request->is('post')) {
            $sport = $this->Sports->patchEntity($sport, $this->request->getData());

            if ($this->Sports->save($sport)) {
                $response = [
                    'success' => true,
                    'message' => 'Sport has been added successfully.',
                    'newOption' => [
                        'value' => $sport->id,
                        'text' => $sport->sport_name,
                    ],
                ];
            } else {
                $errors = [];
                foreach ($sport->getErrors() as $field => $fieldErrors) {
                    foreach ($fieldErrors as $error) {
                        $errors[] = ucfirst($field) . ': ' . $error;
                    }
                }

                $response = [
                    'success' => false,
                    'errors' => $errors ?: ['Unable to save sport. Please try again.'],
                ];
            }
        } else {
            $response = [
                'success' => false,
                'errors' => ['Invalid request method.'],
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($response));
    }

    /**
     * View sport configurations
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response|null Renders view
     */
    public function configs(string $id): ?Response
    {
        try {
            $sport = $this->Sports->get($id);
            $configs = $this->sportConfigAdminService->getFormattedConfigsForSport((int)$id);

            $this->set(compact('sport', 'configs'));

            return null;
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'index']);
        }
    }

    /**
     * Edit sport configurations
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response|null Renders view or redirects
     */
    public function editConfigs(string $id): ?Response
    {
        try {
            $sport = $this->Sports->get($id);

            if ($this->request->is(['patch', 'post', 'put'])) {
                $configData = $this->request->getData('configs', []);

                if ($this->sportConfigAdminService->saveBulkConfigs((int)$id, $configData)) {
                    $this->Flash->success(__('Sport configurations have been updated.'));

                    return $this->redirect(['action' => 'configs', $id]);
                } else {
                    $this->Flash->error(__('Unable to update sport configurations. Please try again.'));
                }
            }

            $configs = $this->sportConfigAdminService->getFormattedConfigsForSport((int)$id);
            $configs = $this->sportConfigAdminService->normalizeFormattedConfigs($configs);

            $this->set(compact('sport', 'configs'));

            return null;
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Sport not found.'));

            return $this->redirect(['action' => 'index']);
        }
    }

    /**
     * Add a new sport configuration
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response|null Redirects
     */
    public function addConfig(string $id): ?Response
    {
        $this->request->allowMethod(['post']);

        try {
            // Verify sport exists (throws RecordNotFoundException if not)
            $this->Sports->get($id);

            $key = $this->request->getData('config_key');
            $value = $this->request->getData('config_value');
            $description = $this->request->getData('description');

            if (empty($key)) {
                $this->Flash->error(__('Configuration key is required.'));

                return $this->redirect(['action' => 'editConfigs', $id]);
            }

            // Handle array values (like officials)
            if (str_contains($value, ',')) {
                $value = array_map('trim', explode(',', $value));
            }

            $result = $this->sportConfigAdminService->setConfig((int)$id, (string)$key, $value, (string)$description);

            if ($result) {
                $this->Flash->success(__('Configuration added successfully.'));
            } else {
                $this->Flash->error(__('Unable to add configuration. Please try again.'));
            }
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Sport not found.'));
        }

        return $this->redirect(['action' => 'editConfigs', $id]);
    }

    /**
     * Delete a sport configuration
     *
     * @param string $id Sport ID
     * @param string $configKey Configuration key
     * @return \Cake\Http\Response Redirects
     */
    public function deleteConfig(string $id, string $configKey): Response
    {
        $this->request->allowMethod(['delete']);

        try {
            // Verify sport exists (throws RecordNotFoundException if not)
            $this->Sports->get($id);

            if ($this->sportConfigAdminService->deleteConfig((int)$id, $configKey)) {
                $this->Flash->success(__('Configuration deleted successfully.'));
            } else {
                $this->Flash->error(__('Unable to delete configuration.'));
            }
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Sport not found.'));
        }

        return $this->redirect(['action' => 'editConfigs', $id]);
    }

    /**
     * Reset sport configurations to defaults
     *
     * @param string $id Sport ID
     * @return \Cake\Http\Response Redirects
     */
    public function resetConfigs(string $id): Response
    {
        $this->request->allowMethod(['post']);

        try {
            // Verify sport exists (throws RecordNotFoundException if not)
            $this->Sports->get($id);

            $success = $this->sportConfigAdminService->resetToDefaults((int)$id);

            if ($success) {
                $this->Flash->success(__('Sport configurations have been reset to defaults.'));
            } else {
                $this->Flash->error(__('Unable to reset configurations. Please try again.'));
            }
        } catch (RecordNotFoundException $e) {
            $this->Flash->error(__('Sport not found.'));
        }

        return $this->redirect(['action' => 'editConfigs', $id]);
    }
}
