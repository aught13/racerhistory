<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Sport;
use App\Model\Entity\SportStatRegistry;
use App\Model\Table\SportsTable;
use App\Model\Table\SportStatRegistryTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * SportStatsAdminService
 *
 * Owns the full administrative slice for managing sport stat table
 * configurations (SportStatRegistry records). Used exclusively by
 * Admin/SportStatsController to keep that controller free of ORM and
 * business logic.
 *
 * Responsibilities:
 * - Build the index query (filterable by sport) and provide the sport
 *   entity for the current filter.
 * - Return form option lists (sports, contexts, entity types).
 * - Construct new empty entities (pre-seeding sport_id when supplied).
 * - Process add/edit form submissions, including the field-mapping
 *   JSON encoding step, and clear the sport config cache on success.
 * - Handle single-record deletion with cache clearing.
 *
 * Notes:
 * - Keep HTTP concerns (Flash, redirect, allowMethod) in the controller.
 * - Returned array keys are relied on by the controller and tests — do not
 *   rename them without updating call sites.
 * - Field-mapping processing (fields[] + labels[] → JSON) lives here so
 *   neither the controller nor the template needs to know the encoding detail.
 *
 * @property \App\Model\Table\SportStatRegistryTable $sportStatRegistryTable
 * @property \App\Model\Table\SportsTable $sportsTable
 * @property \App\Service\SportConfigService $sportConfigService
 */
class SportStatsAdminService
{
    /**
     * @var \App\Model\Table\SportStatRegistryTable
     */
    private SportStatRegistryTable $sportStatRegistryTable;

    /**
     * @var \App\Model\Table\SportsTable
     */
    private SportsTable $sportsTable;

    /**
     * @var \App\Service\SportConfigService
     */
    private SportConfigService $sportConfigService;

    /**
     * @param \App\Model\Table\SportStatRegistryTable|null $sportStatRegistryTable
     * @param \App\Model\Table\SportsTable|null $sportsTable
     * @param \App\Service\SportConfigService|null $sportConfigService
     */
    public function __construct(
        ?SportStatRegistryTable $sportStatRegistryTable = null,
        ?SportsTable $sportsTable = null,
        ?SportConfigService $sportConfigService = null,
    ) {
        /** @var \App\Model\Table\SportStatRegistryTable $table */
        $table = $sportStatRegistryTable
            ?? TableRegistry::getTableLocator()->get('SportStatRegistry');
        $this->sportStatRegistryTable = $table;

        /** @var \App\Model\Table\SportsTable $sports */
        $sports = $sportsTable
            ?? TableRegistry::getTableLocator()->get('Sports');
        $this->sportsTable = $sports;

        $this->sportConfigService = $sportConfigService ?? new SportConfigService();
    }

    /**
     * Build the index query, optionally filtered to a single sport.
     *
     * The controller is responsible for calling paginate() on the result.
     *
     * @param int|null $sportId Optional sport filter
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function buildIndexQuery(?int $sportId): SelectQuery
    {
        $conditions = [];
        if ($sportId !== null) {
            $conditions['SportStatRegistry.sport_id'] = $sportId;
        }

        return $this->sportStatRegistryTable->find()
            ->contain(['Sports'])
            ->where($conditions)
            ->orderBy([
                'SportStatRegistry.context' => 'ASC',
                'SportStatRegistry.entity_type' => 'ASC',
            ]);
    }

    /**
     * Retrieve the Sport entity used as the current index filter, or null if
     * no filter is active.
     *
     * @param int|null $sportId
     * @return \App\Model\Entity\Sport|null
     */
    public function getFilterSport(?int $sportId): ?Sport
    {
        if ($sportId === null) {
            return null;
        }

        /** @var \App\Model\Entity\Sport|null $sport */
        $sport = $this->sportsTable->find()->where(['id' => $sportId])->first();

        return $sport;
    }

    /**
     * Return the sports select-list for the index/add/edit forms.
     *
     * @return \Cake\Datasource\ResultSetInterface<array<string,mixed>>
     */
    public function getSportsList(): ResultSetInterface
    {
        return $this->sportsTable->find('list')->orderBy(['sport_name' => 'ASC'])->all();
    }

    /**
     * Return the static option arrays used by the add/edit forms.
     *
     * @return array{
     *     sports:\Cake\Datasource\ResultSetInterface<array<string,mixed>>,
     *     contexts:array<string,string>,
     *     entityTypes:array<string,string>
     * }
     */
    public function getFormOptions(): array
    {
        return [
            'sports' => $this->getSportsList(),
            'contexts' => [
                'game' => __('Game'),
                'season' => __('Season'),
                'career' => __('Career'),
            ],
            'entityTypes' => [
                'team' => __('Team'),
                'player' => __('Player'),
                'opponent' => __('Opponent'),
                'box' => __('Box Score'),
            ],
        ];
    }

    /**
     * Create a new empty entity, optionally pre-seeding sport_id.
     *
     * @param int|null $sportId
     * @return \App\Model\Entity\SportStatRegistry
     */
    public function newEntity(?int $sportId = null): SportStatRegistry
    {
        /** @var \App\Model\Entity\SportStatRegistry $entity */
        $entity = $this->sportStatRegistryTable->newEmptyEntity();
        if ($sportId !== null) {
            $entity->sport_id = $sportId;
        }

        return $entity;
    }

    /**
     * Retrieve a single entity with its Sport for the view action.
     *
     * @param int $id Registry ID
     * @return \App\Model\Entity\SportStatRegistry
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getViewEntity(int $id): SportStatRegistry
    {
        /** @var \App\Model\Entity\SportStatRegistry $entity */
        $entity = $this->sportStatRegistryTable->get($id, ['contain' => ['Sports']]);

        return $entity;
    }

    /**
     * Return the entity and decoded field-mapping array for the edit form.
     *
     * @param int $id Registry ID
     * @return array{statRegistry:\App\Model\Entity\SportStatRegistry,mappedFields:array<int,array<string,string>>}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function getEditData(int $id): array
    {
        /** @var \App\Model\Entity\SportStatRegistry $statRegistry */
        $statRegistry = $this->sportStatRegistryTable->get($id);

        $mappedFields = [];
        if (!empty($statRegistry->field_mapping)) {
            $mapping = json_decode($statRegistry->field_mapping, true);
            if (is_array($mapping)) {
                foreach ($mapping as $field => $label) {
                    $mappedFields[] = ['field' => $field, 'label' => $label];
                }
            }
        }

        return compact('statRegistry', 'mappedFields');
    }

    /**
     * Process and save a new registry entry.
     *
     * Encodes fields[]/labels[] pairs into JSON field_mapping before saving,
     * then clears the sport config cache on success.
     *
     * @param array<string,mixed> $data Form data
     * @param int|null $sportId Optional sport_id pre-seed
     * @return array{success:bool,statRegistry:\App\Model\Entity\SportStatRegistry,sportId?:int}
     */
    public function add(array $data, ?int $sportId = null): array
    {
        $statRegistry = $this->newEntity($sportId);
        $data = $this->processFieldMapping($data);
        $statRegistry = $this->sportStatRegistryTable->patchEntity($statRegistry, $data);

        if ($this->sportStatRegistryTable->save($statRegistry)) {
            $this->sportConfigService->clearCache((int)$statRegistry->sport_id);

            return ['success' => true, 'statRegistry' => $statRegistry, 'sportId' => (int)$statRegistry->sport_id];
        }

        return ['success' => false, 'statRegistry' => $statRegistry];
    }

    /**
     * Process and save edits to an existing registry entry.
     *
     * @param int $id Registry ID
     * @param array<string,mixed> $data Form data
     * @return array{success:bool,statRegistry:\App\Model\Entity\SportStatRegistry}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function edit(int $id, array $data): array
    {
        /** @var \App\Model\Entity\SportStatRegistry $statRegistry */
        $statRegistry = $this->sportStatRegistryTable->get($id);
        $data = $this->processFieldMapping($data);
        $statRegistry = $this->sportStatRegistryTable->patchEntity($statRegistry, $data);

        if ($this->sportStatRegistryTable->save($statRegistry)) {
            $this->sportConfigService->clearCache((int)$statRegistry->sport_id);

            return ['success' => true, 'statRegistry' => $statRegistry];
        }

        return ['success' => false, 'statRegistry' => $statRegistry];
    }

    /**
     * Delete a registry entry and clear the sport config cache.
     *
     * @param int $id Registry ID
     * @return array{success:bool,sportId:int}
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function delete(int $id): array
    {
        /** @var \App\Model\Entity\SportStatRegistry $statRegistry */
        $statRegistry = $this->sportStatRegistryTable->get($id);
        $sportId = (int)$statRegistry->sport_id;

        if ($this->sportStatRegistryTable->delete($statRegistry)) {
            $this->sportConfigService->clearCache($sportId);

            return ['success' => true, 'sportId' => $sportId];
        }

        return ['success' => false, 'sportId' => $sportId];
    }

    /**
     * Encode fields[]/labels[] POST arrays into the field_mapping JSON field.
     *
     * Only runs the encoding when both keys are present and non-empty arrays.
     * Passes the data through unchanged otherwise.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function processFieldMapping(array $data): array
    {
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

        return $data;
    }
}
