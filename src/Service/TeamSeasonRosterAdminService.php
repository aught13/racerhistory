<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\TeamSeasonRosters;
use App\Model\Table\TeamSeasonRostersTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * TeamSeasonRosterAdminService
 *
 * Owns the full admin management slice for TeamSeasonRosters, including
 * single-row edit/delete, multi-row add/edit workflows, popup JSON creation,
 * and support datasets required by roster forms.
 *
 * Notes:
 * - Keep row processing deterministic; tests assert exact saved/deleted counts.
 * - Do not move Flash/Redirect concerns into this service.
 * - Preserve payload contracts for ajaxAdd and bulk update helpers.
 */
class TeamSeasonRosterAdminService
{
    private TeamSportContextService $teamSportContextService;

    private RbacPermissionService $rbacPermissionService;

    private AdminDataScopeService $adminDataScopeService;

    /**
     * @param \App\Service\TeamSportContextService|null $teamSportContextService Team sport context helper
     * @param \App\Service\AdminDataScopeService|null $adminDataScopeService Role data scope resolver
     */
    public function __construct(
        ?TeamSportContextService $teamSportContextService = null,
        ?AdminDataScopeService $adminDataScopeService = null,
    ) {
        $this->teamSportContextService = $teamSportContextService ?? new TeamSportContextService();
        $this->rbacPermissionService = new RbacPermissionService();
        $this->adminDataScopeService = $adminDataScopeService ?? new AdminDataScopeService();
    }

    /**
     * Return detail page data.
     *
     * @param string|int $id Team season roster identifier
     * @param mixed $identity Current authenticated identity.
     * @return array{teamSeasonRoster:\App\Model\Entity\TeamSeasonRosters}
     */
    public function getViewData(int|string $id, mixed $identity = null): array
    {
        $teamSeasonRoster = $this->findScopedRoster($id, $identity);

        return compact('teamSeasonRoster');
    }

    /**
     * Return add form data for multi-row creation.
     *
     * @param int|null $teamSeasonId Optional pre-selected team season id
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getAddFormData(?int $teamSeasonId, mixed $identity = null): array
    {
        if ($teamSeasonId !== null && !$this->adminDataScopeService->canAccessTeamSeason($identity, $teamSeasonId)) {
            $teamSeasonId = null;
        }

        $teamSeasonsList = $this->getScopedTeamSeasonOptions($identity);
        $sports = $this->teamSportContextService->getLegacySportOptions();

        return compact('teamSeasonId', 'teamSeasonsList', 'sports');
    }

    /**
     * Save multiple roster rows.
     *
     * @param int $teamSeasonId Team season id
     * @param array<mixed> $rows Submitted rows
     * @param mixed $identity Current authenticated identity.
     * @return array{saved:int,errors:array<int,string>}
     */
    public function saveBulkAddRows(int $teamSeasonId, array $rows, mixed $identity = null): array
    {
        $saved = 0;
        $errors = [];
        if (!$this->adminDataScopeService->canAccessTeamSeason($identity, $teamSeasonId)) {
            return ['saved' => 0, 'errors' => ['The selected team season is outside your role scope.']];
        }

        foreach ($rows as $index => $rowData) {
            if (!is_array($rowData)) {
                continue;
            }
            $personId = (int)($rowData['person_id'] ?? 0);
            if ($personId <= 0) {
                continue;
            }

            $entityData = [
                'team_season_id' => $teamSeasonId,
                'person_id' => $personId,
                'roster_year' => $rowData['roster_year'] ?? null,
                'roster_number' => $rowData['roster_number'] ?? null,
                'roster_position' => $rowData['roster_position'] ?? null,
                'roster_height' => $rowData['roster_height'] ?? null,
                'roster_weight' => $rowData['roster_weight'] ?? null,
            ];

            $entity = $this->getTeamSeasonRostersTable()->newEntity($entityData);
            if ($this->getTeamSeasonRostersTable()->save($entity)) {
                $saved++;
            } else {
                $errors[] = __('Row {0}: could not save.', (int)$index + 1);
            }
        }

        return compact('saved', 'errors');
    }

    /**
     * Return bulk edit form data.
     *
     * @param int|null $teamSeasonId Selected team season id
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getBulkEditFormData(?int $teamSeasonId, mixed $identity = null): array
    {
        if ($teamSeasonId !== null && !$this->adminDataScopeService->canAccessTeamSeason($identity, $teamSeasonId)) {
            throw new RecordNotFoundException('Team season not found.');
        }

        $existingRosters = [];
        if ($teamSeasonId) {
            $existingRosters = $this->getTeamSeasonRostersTable()->find()
                ->where(['team_season_id' => $teamSeasonId])
                ->contain(['Persons'])
                ->orderByAsc('roster_number')
                ->all()
                ->toArray();
        }

        $teamSeasonsList = $this->getScopedTeamSeasonOptions($identity);
        $sports = $this->teamSportContextService->getLegacySportOptions();

        return compact('teamSeasonId', 'teamSeasonsList', 'sports', 'existingRosters');
    }

    /**
     * Process bulk edit submission.
     *
     * @param int $teamSeasonId Team season id
     * @param array<mixed> $rows Submitted rows
     * @param mixed $identity Current authenticated identity.
     * @return array{saved:int,deletedCount:int,errors:array<int,string>,teamSeasonId:int}
     */
    public function processBulkUpdate(int $teamSeasonId, array $rows, mixed $identity = null): array
    {
        if (!$this->adminDataScopeService->canAccessTeamSeason($identity, $teamSeasonId)) {
            return [
                'saved' => 0,
                'deletedCount' => 0,
                'errors' => ['The selected team season is outside your role scope.'],
                'teamSeasonId' => $teamSeasonId,
            ];
        }

        $submittedIds = [];
        foreach ($rows as $rowData) {
            if (!is_array($rowData)) {
                continue;
            }
            $existingId = (int)($rowData['id'] ?? 0);
            if ($existingId > 0) {
                $submittedIds[] = $existingId;
            }
        }

        $allExistingIds = $this->getTeamSeasonRostersTable()->find()
            ->where(['team_season_id' => $teamSeasonId])
            ->all()
            ->extract('id')
            ->toArray();

        $toDelete = array_diff($allExistingIds, $submittedIds);
        $deletedCount = 0;
        if ($toDelete !== []) {
            $deletedCount = $this->getTeamSeasonRostersTable()->deleteAll([
                'id IN' => array_values($toDelete),
                'team_season_id' => $teamSeasonId,
            ]);
        }

        $saved = 0;
        $errors = [];
        foreach ($rows as $index => $rowData) {
            if (!is_array($rowData)) {
                continue;
            }
            $personId = (int)($rowData['person_id'] ?? 0);
            if ($personId <= 0) {
                continue;
            }

            $existingId = (int)($rowData['id'] ?? 0);
            $entityData = [
                'team_season_id' => $teamSeasonId,
                'person_id' => $personId,
                'roster_year' => $rowData['roster_year'] ?? null,
                'roster_number' => $rowData['roster_number'] ?? null,
                'roster_position' => $rowData['roster_position'] ?? null,
                'roster_height' => $rowData['roster_height'] ?? null,
                'roster_weight' => $rowData['roster_weight'] ?? null,
            ];

            if ($existingId > 0) {
                $entity = $this->getTeamSeasonRostersTable()->find()
                    ->where(['id' => $existingId, 'team_season_id' => $teamSeasonId])
                    ->first();
                if (!$entity) {
                    $errors[] = __('Row {0}: record not found.', (int)$index + 1);
                    continue;
                }
                $entity = $this->getTeamSeasonRostersTable()->patchEntity($entity, $entityData);
            } else {
                $entity = $this->getTeamSeasonRostersTable()->newEntity($entityData);
            }

            if ($this->getTeamSeasonRostersTable()->save($entity)) {
                $saved++;
            } else {
                $errors[] = __('Row {0}: could not save.', (int)$index + 1);
            }
        }

        return compact('saved', 'deletedCount', 'errors', 'teamSeasonId');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Team season roster identifier
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function getEditFormData(int|string $id, mixed $identity = null): array
    {
        $teamSeasonRoster = $this->findScopedRoster($id, $identity);

        $teamSeasonsList = $this->getScopedTeamSeasonOptions($identity);
        $personIdExisting = (int)$teamSeasonRoster->get('person_id');
        $persons = (new PersonService())->getPersonsList(200, $personIdExisting ?: null);
        $sports = $this->teamSportContextService->getLegacySportOptions();

        return compact('teamSeasonRoster', 'teamSeasonsList', 'persons', 'sports');
    }

    /**
     * Save existing roster row.
     *
     * @param string|int $id Team season roster identifier
     * @param array<string,mixed> $data Request payload
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,teamSeasonRoster:\App\Model\Entity\TeamSeasonRosters}
     */
    public function saveExistingRoster(int|string $id, array $data, mixed $identity = null): array
    {
        $teamSeasonRoster = $this->findScopedRoster($id, $identity);
        if (
            array_key_exists('team_season_id', $data)
            && !$this->adminDataScopeService->canAccessTeamSeason($identity, (int)$data['team_season_id'])
        ) {
            return ['success' => false, 'teamSeasonRoster' => $teamSeasonRoster];
        }

        $teamSeasonRoster = $this->getTeamSeasonRostersTable()->patchEntity($teamSeasonRoster, $data);
        $success = (bool)$this->getTeamSeasonRostersTable()->save($teamSeasonRoster);

        return compact('success', 'teamSeasonRoster');
    }

    /**
     * Delete single roster row.
     *
     * @param string|int $id Team season roster identifier
     * @param mixed $identity Current authenticated identity
     * @return array{success:bool,teamSeasonId:int}
     */
    public function deleteRoster(int|string $id, mixed $identity = null): array
    {
        $scoped = $this->rbacPermissionService->scopeQuery(
            $identity,
            'TeamSeasonRosters',
            $this->getTeamSeasonRostersTable()->find(),
            'delete',
            'id',
        );
        $scoped = $this->adminDataScopeService->scopeTeamOwnedQuery($identity, 'TeamSeasonRosters', $scoped);
        /** @var \App\Model\Entity\TeamSeasonRosters|null $teamSeasonRoster */
        $teamSeasonRoster = $scoped->where(['TeamSeasonRosters.id' => (int)$id])->first();
        if ($teamSeasonRoster === null) {
            return ['success' => false, 'teamSeasonId' => 0];
        }

        $teamSeasonId = (int)$teamSeasonRoster->get('team_season_id');
        $success = (bool)$this->getTeamSeasonRostersTable()->delete($teamSeasonRoster);

        return compact('success', 'teamSeasonId');
    }

    /**
     * Validate and normalize bulk identifier input.
     *
     * @param array<mixed> $rawIds Raw identifier list
     * @return array<int>
     */
    public function sanitizeIdentifierList(array $rawIds): array
    {
        $filtered = array_values(array_filter($rawIds, static function ($value) {
            return $value !== '' && $value !== null && ctype_digit((string)$value);
        }));

        return array_map('intval', $filtered);
    }

    /**
     * Bulk delete roster rows.
     *
     * @param array<mixed> $rawIds Raw identifier list
     * @param mixed $identity Current authenticated identity
     * @return array{validSelection:bool,validRosters:bool,deletedCount:int,teamSeasonId:int|null}
     */
    public function bulkDeleteRosters(array $rawIds, mixed $identity = null): array
    {
        $teamSeasonRosterIds = $this->sanitizeIdentifierList($rawIds);
        if ($teamSeasonRosterIds === []) {
            return [
                'validSelection' => false,
                'validRosters' => false,
                'deletedCount' => 0,
                'teamSeasonId' => null,
            ];
        }

        $allowedQuery = $this->rbacPermissionService->scopeQuery(
            $identity,
            'TeamSeasonRosters',
            $this->getTeamSeasonRostersTable()->find(),
            'delete',
            'id',
        );
        $allowedIds = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasonRosters',
            $allowedQuery,
        )
            ->select(['TeamSeasonRosters.id'])
            ->where(['TeamSeasonRosters.id IN' => $teamSeasonRosterIds])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        $allowedIds = array_values(array_map('intval', $allowedIds));
        if ($allowedIds === []) {
            return [
                'validSelection' => true,
                'validRosters' => false,
                'deletedCount' => 0,
                'teamSeasonId' => null,
            ];
        }

        /** @var \App\Model\Entity\TeamSeasonRosters|null $firstRoster */
        $firstRoster = $this->getTeamSeasonRostersTable()->find()
            ->where(['id IN' => $allowedIds])
            ->first();

        if (!$firstRoster) {
            return [
                'validSelection' => true,
                'validRosters' => false,
                'deletedCount' => 0,
                'teamSeasonId' => null,
            ];
        }

        $teamSeasonId = (int)$firstRoster->get('team_season_id');
        $deletedCount = $this->getTeamSeasonRostersTable()->deleteAll(['id IN' => $allowedIds]);

        return [
            'validSelection' => true,
            'validRosters' => true,
            'deletedCount' => $deletedCount,
            'teamSeasonId' => $teamSeasonId,
        ];
    }

    /**
     * Save a roster from popup add form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function createRosterFromPopup(array $data, mixed $identity = null): array
    {
        /** @var \App\Model\Entity\TeamSeasonRosters $teamSeasonRoster */
        $teamSeasonRoster = $this->getTeamSeasonRostersTable()->newEmptyEntity();
        if (!$this->adminDataScopeService->canAccessTeamSeason($identity, (int)($data['team_season_id'] ?? 0))) {
            return [
                'success' => false,
                'errors' => ['The selected team season is outside your role scope.'],
            ];
        }
        $teamSeasonRoster = $this->getTeamSeasonRostersTable()->patchEntity($teamSeasonRoster, $data);

        if ($this->getTeamSeasonRostersTable()->save($teamSeasonRoster)) {
            $personId = (int)$teamSeasonRoster->get('person_id');
            $personLabel = (new PersonService())->getDisplayLabel($personId);

            return [
                'success' => true,
                'message' => 'Team season roster has been added successfully.',
                'newOption' => [
                    'value' => (int)$teamSeasonRoster->get('id'),
                    'text' => $personLabel,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($teamSeasonRoster)
                ?: ['Unable to save team season roster. Please try again.'],
        ];
    }

    /**
     * Convert entity validation errors into frontend-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity TeamSeasonRoster entity
     * @return array<int,string>
     */
    private function collectValidationErrors(EntityInterface $entity): array
    {
        $errors = [];
        foreach ($entity->getErrors() as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $errors[] = ucfirst((string)$field) . ': ' . (string)$error;
            }
        }

        return $errors;
    }

    /**
     * @param string|int $id Roster identifier.
     * @param mixed $identity Current authenticated identity.
     * @return \App\Model\Entity\TeamSeasonRosters
     * @throws \Cake\Datasource\Exception\RecordNotFoundException If outside the role scope.
     */
    private function findScopedRoster(int|string $id, mixed $identity): TeamSeasonRosters
    {
        $scoped = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'TeamSeasonRosters',
            $this->getTeamSeasonRostersTable()->find(),
        )
            ->where(['TeamSeasonRosters.id' => (int)$id])
            ->contain(['TeamSeasons' => ['Teams', 'Seasons'], 'Persons'])
            ->first();

        if (!$scoped instanceof TeamSeasonRosters) {
            throw new RecordNotFoundException('Roster entry not found.');
        }

        return $scoped;
    }

    /**
     * @param mixed $identity Current authenticated identity.
     * @return array<int,string>
     */
    private function getScopedTeamSeasonOptions(mixed $identity): array
    {
        $options = (new TeamSeasonService())->getTeamSeasonsListForRosterSelect(200);
        $allowedIds = $this->adminDataScopeService->getAllowedTeamSeasonIds($identity);
        if ($allowedIds === null) {
            return $options;
        }

        return array_intersect_key($options, array_fill_keys($allowedIds, true));
    }

    /**
     * @return \App\Model\Table\TeamSeasonRostersTable
     */
    private function getTeamSeasonRostersTable(): TeamSeasonRostersTable
    {
        /** @var \App\Model\Table\TeamSeasonRostersTable $table */
        $table = TableRegistry::getTableLocator()->get('TeamSeasonRosters');

        return $table;
    }
}
