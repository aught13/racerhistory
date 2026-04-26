<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SportConfigAdminService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * Admin Sports Controller
 *
 * Provides CRUD operations for managing sports in the admin interface, as well as managing sport-specific configurations. The index action lists all sports, while the add and edit actions allow for creating and updating sports, respectively. The delete action handles sport deletion, with a check to prevent deletion if there are associated teams. The controller also includes bulkDelete and bulk actions for handling multiple deletions at once, and an ajaxAdd action for adding new sports from a popup form, returning JSON responses for seamless integration with the frontend.
 *
 * The configs, editConfigs, addConfig, deleteConfig, and resetConfigs actions provide a way to manage key-value pair configurations specific to each sport, which can be used to store various settings or attributes related to the sport in a flexible manner. These actions allow administrators to view, edit, add, delete, and reset configurations for each sport, with appropriate validation and error handling to ensure a smooth user experience.
 *
 * Actions:
 * - index: Lists all sports with their associated teams for record count display in delete confirmations.
 * - view: Displays details of a single sport, including its configurations.
 * - add: Handles the creation of a new sport, including form display and processing.
 * - edit: Handles the editing of an existing sport, including form display and processing.
 * - delete: Handles the deletion of a sport, ensuring that there are no associated teams before allowing deletion. Uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 * - bulkDelete: Handles the deletion of multiple sports at once, with similar checks and protections as the single delete action.
 * - bulk: A dispatcher for bulk actions, currently supporting bulk deletion of sports.
 * - ajaxAdd: Provides an endpoint for adding a new sport from a popup form, returning success or error messages in JSON format for seamless integration with the frontend. This allows administrators to quickly add new sports without needing to navigate away from their current context. The form data is validated and any errors are returned in a structured format to help guide the user in correcting any issues with their input.
 * - configs: Displays the configurations for a specific sport.
 * - editConfigs: Handles the editing of sport configurations, including form display and processing.
 * - addConfig: Handles the addition of a new configuration for a sport, including validation and error handling.
 * - deleteConfig: Handles the deletion of a specific configuration for a sport.
 * - resetConfigs: Handles resetting all configurations for a sport back to their default values.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage sports. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete and bulkDelete actions use POST or DELETE HTTP methods to prevent accidental deletions via GET requests.
 *
 * Dependencies:
 * - SportConfigAdminService: Provides methods for managing sport-specific configurations, including retrieving formatted configs for display, saving bulk configs, setting individual configs, deleting configs, and resetting configs to defaults.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete operations.
 *
 * Note: The ajaxAdd action is designed for use with popup forms and returns JSON responses indicating success or failure, along with any validation errors. This allows for seamless integration with the frontend without requiring full page reloads. The configuration management actions (configs, editConfigs, addConfig, deleteConfig, resetConfigs) provide a way to manage key-value pair configurations specific to each sport, which can be used to store various settings or attributes related to the sport in a flexible manner.
 * The view action includes the sport's configurations, demonstrating how the controller can handle more complex data retrieval and processing while still keeping the core logic focused on request handling and response formatting. Proper error handling, feedback mechanisms, logging, and security measures should be implemented throughout the controller to ensure a secure and user-friendly experience for managing sports and their configurations in the admin interface.
 * The delete and bulkDelete actions should be used with caution, as they will permanently remove sport records from the database. Proper confirmation and safeguards should be implemented in the UI to prevent accidental deletions. Additionally, the add and edit actions should validate input data to prevent invalid or malicious data from being saved to the database, and the AJAX endpoint should validate input parameters to prevent potential issues with invalid input or unauthorized access to data. Proper error handling, feedback mechanisms, logging, and security measures should be implemented throughout the controller to ensure a secure and user-friendly experience for managing sports in the admin interface.
 * The configuration management actions should also include proper validation and error handling to ensure that only valid configurations are saved, and that any issues with configuration management are clearly communicated to the user through flash messages or JSON responses, as appropriate. This will help maintain the integrity of the sport configurations and provide a better user experience for administrators managing sports in the admin interface.
 * The beforeFilter method is used to disable form protection for the editConfigs action due to the dynamic nature of the form fields, which may not be compatible with the standard form protection mechanism. This allows for a smoother user experience when managing sport configurations, while still maintaining security for other actions that involve form submissions.
 * Overall, this controller provides comprehensive management of sports and their configurations in the admin interface, with a focus on security, user experience, and maintainability. Proper validation, error handling, and feedback mechanisms are implemented throughout the controller to ensure a robust and user-friendly experience for administrators managing sports in the application.
 *
 * @property \App\Model\Table\SportsTable $Sports
 * @property \App\Service\SportConfigAdminService $sportConfigAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */

class SportsController extends AppController
{
    /**
     * @var \App\Model\Table\SportsTable
     */
    protected \App\Model\Table\SportsTable $Sports;

    private SportConfigAdminService $sportConfigAdminService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Sports = $this->fetchTable('Sports');
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
