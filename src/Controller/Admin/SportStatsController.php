<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SportStatsAdminService;
use Cake\Http\Response;

/**
 * Admin Sport Stats Controller
 *
 * Thin HTTP orchestrator for managing sport stat table configurations
 * (SportStatRegistry records). All ORM access and business logic live in
 * SportStatsAdminService; this controller extracts request data, delegates,
 * then sets flash messages and performs redirects.
 *
 * The beforeFilter disables FormProtection for add/edit because those actions
 * render dynamic field-mapping rows whose keys cannot be predicted at
 * form-render time and would fail the token check.
 *
 * Actions:
 * - index: Lists all stat-table configs, optionally filtered by sport.
 * - view: Displays a single stat-table config with its sport.
 * - add: Create form and POST handler; pre-selects sport when sportId passed.
 * - edit: Edit form and POST handler; decodes field_mapping for the form.
 * - delete: POST/DELETE handler; clears sport config cache on success.
 *
 * Notes:
 * - Do not put ORM queries or field-mapping encoding here; that belongs in
 *   SportStatsAdminService.
 * - Flash strings are tested directly in SportStatsControllerTest — keep them
 *   stable.
 *
 * @property \App\Service\SportStatsAdminService $sportStatsAdminService
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class SportStatsController extends AppController
{
    /**
     * @var \App\Service\SportStatsAdminService Admin service for sport stat registry CRUD
     */
    protected SportStatsAdminService $sportStatsAdminService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->sportStatsAdminService = new SportStatsAdminService();
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
     * Index method — list all stat tables with optional sport filter.
     *
     * @param string|null $sportId Sport ID to filter by
     * @return \Cake\Http\Response|null|void
     */
    public function index(?string $sportId = null)
    {
        $sportIdInt = $sportId !== null ? (int)$sportId : null;
        $query = $this->sportStatsAdminService->buildIndexQuery($sportIdInt);
        $statRegistries = $this->paginate($query);

        $sport = $this->sportStatsAdminService->getFilterSport($sportIdInt);
        $options = $this->sportStatsAdminService->getFormOptions();

        $this->set(compact('statRegistries', 'sport', 'sportId'));
        $this->set('sports', $options['sports']);
    }

    /**
     * View a single stat-table configuration.
     *
     * @param string $id Registry ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(string $id)
    {
        $statRegistry = $this->sportStatsAdminService->getViewEntity((int)$id);
        $this->set(compact('statRegistry'));
    }

    /**
     * Add new stat registry entry.
     *
     * @param string|null $sportId Optional sport ID to pre-select
     * @return \Cake\Http\Response|null|void Redirects on successful add
     */
    public function add(?string $sportId = null)
    {
        $sportIdInt = $sportId !== null ? (int)$sportId : null;

        if ($this->request->is('post')) {
            $result = $this->sportStatsAdminService->add(
                $this->request->getData(),
                $sportIdInt,
            );

            if ($result['success']) {
                $this->Flash->success(__('The stat table configuration has been saved.'));

                return $this->redirect(['action' => 'index', $result['sportId']]);
            }

            $this->Flash->error(__('The stat table configuration could not be saved. Please try again.'));
            $statRegistry = $result['statRegistry'];
        } else {
            $statRegistry = $this->sportStatsAdminService->newEntity($sportIdInt);
        }

        $options = $this->sportStatsAdminService->getFormOptions();
        $this->set(compact('statRegistry'));
        $this->set($options);
    }

    /**
     * Edit an existing stat-table configuration.
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response|null|void Redirects on successful edit
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(string $id)
    {
        if ($this->request->is(['patch', 'post', 'put'])) {
            $result = $this->sportStatsAdminService->edit((int)$id, $this->request->getData());

            if ($result['success']) {
                $this->Flash->success(__('The stat table configuration has been updated.'));

                return $this->redirect(['action' => 'view', $id]);
            }

            $this->Flash->error(__('The stat table configuration could not be updated. Please try again.'));
            $statRegistry = $result['statRegistry'];
            $mappedFields = [];
        } else {
            $editData = $this->sportStatsAdminService->getEditData((int)$id);
            $statRegistry = $editData['statRegistry'];
            $mappedFields = $editData['mappedFields'];
        }

        $options = $this->sportStatsAdminService->getFormOptions();
        $this->set(compact('statRegistry', 'mappedFields'));
        $this->set($options);
    }

    /**
     * Delete a stat-table configuration.
     *
     * @param string $id Stat registry ID
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $result = $this->sportStatsAdminService->delete((int)$id);

        if ($result['success']) {
            $this->Flash->success(__('The stat table configuration has been deleted.'));
        } else {
            $this->Flash->error(__('The stat table configuration could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index', $result['sportId']]);
    }
}
