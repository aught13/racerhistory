<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

/**
 * Admin SportStats Controller
 *
 * Handles administrative sport statistics registry management operations.
 * Provides functionality for configuring sport-specific statistic tables and field mappings.
 *
 * @property \App\Model\Table\SportStatRegistryTable $SportStatRegistry
 * @property \App\Service\SportConfigService $SportConfig
 */
class SportStatsController extends AppController
{
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
        $this->fetchTable('SportStatRegistry');
        $this->loadService('SportConfig');
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
            $conditions['SportStatRegistry.sport_id'] = $sportId;
            $sport = $this->fetchTable('Sports')->get($sportId);
        }

        $this->paginate = [
            'contain' => ['Sports'],
            'conditions' => $conditions,
            'order' => ['SportStatRegistry.context' => 'ASC', 'SportStatRegistry.entity_type' => 'ASC'],
        ];

        $statRegistries = $this->paginate($this->SportStatRegistry);
        $sports = $this->fetchTable('Sports')->find('list')->order('sport_name')->all();

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

        $sports = $this->fetchTable('Sports')->find('list')->order('sport_name')->all();
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

        $sports = $this->fetchTable('Sports')->find('list')->order('sport_name')->all();
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
