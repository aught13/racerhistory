<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SportConfigAdminService;
use App\Service\SportsAdminService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Sports Controller
 *
 * Thin HTTP orchestrator for managing Sport records and their key-value
 * configurations. CRUD and popup-add logic is owned by SportsAdminService;
 * config-management actions delegate to SportConfigAdminService. This
 * controller only extracts request data, calls the appropriate service method,
 * then sets flash messages and redirects.
 *
 * Actions:
 * - index: Lists all sports (with Teams for count display in confirmations).
 * - view: Displays a single sport with Teams and formatted configs.
 * - add: Add form and POST handler.
 * - edit: Edit form and POST handler.
 * - delete: POST/DELETE handler for a single sport.
 * - bulkDelete: POST handler for multiple sport deletions.
 * - bulk: Dispatcher that routes bulk_action to bulkDelete.
 * - ajaxAdd: JSON endpoint for popup-form sport creation.
 * - configs: Displays the key-value configs for a sport.
 * - editConfigs: Edit form and POST handler for bulk config save.
 * - addConfig: POST-only handler for adding a single config key.
 * - deleteConfig: DELETE handler for a single config key.
 * - resetConfigs: POST-only handler to reset all configs to defaults.
 *
 * Notes:
 * - CRUD and popup-add ORM must stay in SportsAdminService, not here.
 * - Config ORM must stay in SportConfigAdminService, not here.
 * - Flash strings and JSON response shapes are asserted in tests — keep stable.
 * - beforeFilter disables FormProtection for editConfigs due to dynamic rows.
 *
 * @property \App\Service\SportsAdminService $sportsAdminService
 * @property \App\Service\SportConfigAdminService $sportConfigAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */

class SportsController extends AppController
{
    /**
     * @var \App\Service\SportsAdminService Admin service for sports CRUD and popup-add
     */
    private SportsAdminService $sportsAdminService;

    /**
     * @var \App\Service\SportConfigAdminService Admin service for sport config management
     */
    private SportConfigAdminService $sportConfigAdminService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->sportsAdminService = new SportsAdminService();
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
        $sports = $this->sportsAdminService->getIndexData();
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
        $sport = $this->sportsAdminService->getViewEntity($id);
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
        if ($this->request->is('post')) {
            $result = $this->sportsAdminService->add($this->request->getData());

            if ($result['success']) {
                $this->Flash->success(__('The sport has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sport could not be saved. Please, try again.'));
            $sport = $result['sport'];
        } else {
            $sport = $this->sportsAdminService->newEntity();
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
        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->sportsAdminService->edit($id, $this->request->getData());

            if ($result['success']) {
                $this->Flash->success(__('The sport has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The sport could not be saved. Please, try again.'));
            $sport = $result['sport'];
        } else {
            $sport = $this->sportsAdminService->getEditEntity($id);
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

        if ($this->sportsAdminService->delete($id)) {
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

        $deletedCount = $this->sportsAdminService->bulkDelete($sportIds);

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
        if ($this->request->is('post')) {
            $result = $this->sportsAdminService->createSportFromPopup($this->request->getData());

            return $this->response
                ->withType('application/json')
                ->withStringBody(json_encode($result));
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode([
                'success' => false,
                'errors' => ['Invalid request method.'],
            ]));
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
            $sport = $this->sportsAdminService->getViewEntity($id);
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
            $sport = $this->sportsAdminService->getViewEntity($id);

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
            $this->sportsAdminService->getViewEntity($id);

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
            $this->sportsAdminService->getViewEntity($id);

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
            $this->sportsAdminService->getViewEntity($id);

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
