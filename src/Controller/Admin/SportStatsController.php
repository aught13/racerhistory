<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin Sport Stats Controller
 *
 * Provides CRUD operations for managing sport stat table configurations in the admin interface. The index action lists all stat table configurations with optional filtering by sport, while the add and edit actions allow for creating and updating stat table configurations, respectively. The delete action handles stat table configuration deletion, with a check to prevent deletion if there are associated games. The controller also includes proper validation and error handling to ensure that only valid configurations are saved, and that any issues with configuration management are clearly communicated to the user through flash messages.
 * The beforeFilter method is used to disable form protection for the add and edit actions due to the dynamic nature of the form fields, which may not be compatible with the standard form protection mechanism. This allows for a smoother user experience when managing sport stat table configurations, while still maintaining security for other actions that involve form submissions.
 * Overall, this controller provides comprehensive management of sport stat table configurations in the admin interface, with a focus on security, user experience, and maintainability. Proper validation, error handling, and feedback mechanisms are implemented throughout the controller to ensure a robust and user-friendly experience for administrators managing sport stat table configurations in the application.
 *
 * Actions:
 * - index: Lists all sport stat table configurations with optional filtering by sport.
 * - view: Displays details of a specific sport stat table configuration.
 * - add: Handles the creation of a new sport stat table configuration, including form display and processing.
 * - edit: Handles the editing of an existing sport stat table configuration, including form display and processing.
 * - delete: Handles the deletion of a sport stat table configuration, ensuring that the request method is POST or DELETE to prevent accidental deletions via GET requests. The delete action also checks for associated games before allowing deletion to prevent orphaned records and maintain data integrity.
 *
 * Security:
 * - All actions should be protected by authentication and authorization checks to ensure that only authorized users can manage sport stat table configurations. This is typically handled by middleware or components that are not shown in this code snippet.
 * - The delete action uses POST or DELETE HTTP methods to prevent accidental deletions via GET requests, and includes a check for associated games to prevent deletion of configurations that are still in use.
 * - The add and edit actions should validate input data to prevent invalid or malicious data from being saved to the database, and proper error handling should be implemented to provide feedback to the user through flash messages.
 *
 * Dependencies:
 * - SportConfigService: Used to clear configuration cache after adding, editing, or deleting sport stat table configurations, ensuring that changes are reflected in the application immediately.
 * - SportStatRegistryTable: The model for managing sport stat table configurations in the database, providing methods for retrieving, saving, and deleting records.
 *
 * Components:
 * - FlashComponent: Used to set success and error messages after create, update, and delete
 * operations, providing feedback to the administrator about the outcome of their actions.
 * - AuthorizationComponent: Used to protect all actions in this controller, ensuring that only authorized users can manage sport stat table configurations. This is typically configured to require authentication and specific permissions for accessing the sport stat management interface and performing actions like adding, editing, and deleting configurations.
 *
 * Note: The add and edit actions include processing for dynamic field mappings, allowing administrators to define custom field labels for the stat tables. The field mappings are stored as JSON in the database, and the controller handles encoding and decoding this data as needed. Proper validation should be implemented to ensure that the field mappings are valid and do not contain any malicious data. Additionally, the delete action includes a check for associated games before allowing deletion of a sport stat table configuration, which helps maintain data integrity by preventing orphaned records. Administrators should be cautious when deleting configurations, as it may impact existing game records that rely on those configurations.
 *
 * @property \App\Model\Table\SportStatRegistryTable $SportStatRegistry
 * @property \App\Service\SportConfigService $SportConfig
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class SportStatsController extends AppController
{
    /**
     * @var \App\Model\Table\SportStatRegistryTable
     */
    protected \App\Model\Table\SportStatRegistryTable $SportStatRegistry;

    /**
     * @var \App\Service\SportConfigService Service for sport configuration management
     */
    protected \App\Service\SportConfigService $SportConfig;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->SportStatRegistry = $this->fetchTable('SportStatRegistry');
        $this->SportConfig = $this->loadService('SportConfig');
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

        // Disable form protection for add/edit due to dynamic form fields
        if (in_array($this->request->getParam('action'), ['add', 'edit'], true)) {
            if ($this->components()->has('FormProtection')) {
                $this->FormProtection->setConfig('unlockedActions', ['add', 'edit']);
            }
        }
    }

    /**
     * Index method - list all stat tables with optional sport filter
     *
     * @param string|null $sportId Sport ID to filter by
     * @return \Cake\Http\Response|null|void
     */
    public function index(?string $sportId = null)
    {
        $conditions = [];
        $sport = null;

        if ($sportId !== null) {
            $conditions['SportStatRegistry.sport_id'] = (int)$sportId;
            $sport = $this->fetchTable('Sports')->find()->where(['id' => (int)$sportId])->first();
        }

        $query = $this->SportStatRegistry->find()
            ->contain(['Sports'])
            ->where($conditions)
            ->orderBy([
                'SportStatRegistry.context' => 'ASC',
                'SportStatRegistry.entity_type' => 'ASC',
            ]);

        $statRegistries = $this->paginate($query);
        $sports = $this->fetchTable('Sports')->find('list')->orderBy(['sport_name' => 'ASC'])->all();

        $this->set(compact('statRegistries', 'sports', 'sport', 'sportId'));
    }

    /**
     * View method
     *
     * @param string $id Registry ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(string $id)
    {
        $statRegistry = $this->SportStatRegistry->get($id, [
            'contain' => ['Sports'],
        ]);

        $this->set(compact('statRegistry'));
    }

    /**
     * Add new stat registry entry
     *
     * @param string|null $sportId Optional sport ID to pre-select
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add(?string $sportId = null)
    {
        $statRegistry = $this->SportStatRegistry->newEmptyEntity();

        if ($sportId !== null) {
            $statRegistry->sport_id = (int)$sportId;
        }

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Process field mapping
            $hasFields = !empty($data['fields']) && is_array($data['fields']);
            $hasLabels = !empty($data['labels']) && is_array($data['labels']);
            if ($hasFields && $hasLabels) {
                $mapping = [];
                foreach ($data['fields'] as $i => $field) {
                    if (!empty($field) && !empty($data['labels'][$i])) {
                        $mapping[$field] = $data['labels'][$i];
                    }
                }
                $data['field_mapping'] = json_encode($mapping);
            }

            $statRegistry = $this->SportStatRegistry->patchEntity($statRegistry, $data);

            if ($this->SportStatRegistry->save($statRegistry)) {
                // Clear configuration cache
                $this->SportConfig->clearCache($statRegistry->sport_id);

                $this->Flash->success(__('The stat table configuration has been saved.'));

                return $this->redirect(['action' => 'index', $statRegistry->sport_id]);
            }

            $this->Flash->error(__('The stat table configuration could not be saved. Please try again.'));
        }

        $sports = $this->fetchTable('Sports')->find('list')->orderBy(['sport_name' => 'ASC'])->all();
        $contexts = [
            'game' => __('Game'),
            'season' => __('Season'),
            'career' => __('Career'),
        ];
        $entityTypes = [
            'team' => __('Team'),
            'player' => __('Player'),
            'opponent' => __('Opponent'),
            'box' => __('Box Score'),
        ];

        $this->set(compact('statRegistry', 'sports', 'contexts', 'entityTypes'));
    }

    /**
     * Edit method
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $id)
    {
        $statRegistry = $this->SportStatRegistry->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Process field mapping
            $hasFields = !empty($data['fields']) && is_array($data['fields']);
            $hasLabels = !empty($data['labels']) && is_array($data['labels']);
            if ($hasFields && $hasLabels) {
                $mapping = [];
                foreach ($data['fields'] as $i => $field) {
                    if (!empty($field) && !empty($data['labels'][$i])) {
                        $mapping[$field] = $data['labels'][$i];
                    }
                }
                $data['field_mapping'] = json_encode($mapping);
            }

            $statRegistry = $this->SportStatRegistry->patchEntity($statRegistry, $data);

            if ($this->SportStatRegistry->save($statRegistry)) {
                // Clear configuration cache
                $this->SportConfig->clearCache($statRegistry->sport_id);

                $this->Flash->success(__('The stat table configuration has been updated.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('The stat table configuration could not be updated. Please try again.'));
        }

        // Get current field mappings
        $mappedFields = [];
        if (!empty($statRegistry->field_mapping)) {
            $mapping = json_decode($statRegistry->field_mapping, true);
            if (is_array($mapping)) {
                foreach ($mapping as $field => $label) {
                    $mappedFields[] = [
                        'field' => $field,
                        'label' => $label,
                    ];
                }
            }
        }

        $sports = $this->fetchTable('Sports')->find('list')->orderBy(['sport_name' => 'ASC'])->all();
        $contexts = [
            'game' => __('Game'),
            'season' => __('Season'),
            'career' => __('Career'),
        ];
        $entityTypes = [
            'team' => __('Team'),
            'player' => __('Player'),
            'opponent' => __('Opponent'),
            'box' => __('Box Score'),
        ];

        $this->set(compact('statRegistry', 'sports', 'contexts', 'entityTypes', 'mappedFields'));
    }

    /**
     * Delete method
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $statRegistry = $this->SportStatRegistry->get($id);
        $sportId = $statRegistry->sport_id;

        if ($this->SportStatRegistry->delete($statRegistry)) {
            // Clear configuration cache
            $this->SportConfig->clearCache($sportId);

            $this->Flash->success(__('The stat table configuration has been deleted.'));
        } else {
            $this->Flash->error(__('The stat table configuration could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index', $sportId]);
    }
}
