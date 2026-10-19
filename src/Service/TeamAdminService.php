<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Team;
use App\Model\Table\TeamsTable;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\TableRegistry;

/**
 * TeamAdminService
 *
 * Owns the complete administrative team management slice used by
 * Admin/TeamsController. This keeps request handling in the controller while
 * centralizing all CRUD orchestration, list preparation, and popup-form save
 * flows in a service layer that can be tested independently.
 *
 * Notes:
 * - Keep this service free of HTTP concerns (Flash, Redirect, Request checks).
 * - Preserve returned array keys because controllers and tests rely on them.
 * - If team form behavior changes, update both standard and popup save paths.
 */
class TeamAdminService
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
     * Return index page data.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string|null $sportKey Optional sport filter.
     * @param string|null $gender Optional gender filter.
     * @return array{teams:\Cake\Datasource\ResultSetInterface}
     */
    public function getIndexData(
        mixed $identity = null,
        ?string $sportKey = null,
        ?string $gender = null,
    ): array {
        $query = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find(),
        );
        $normalizedSportKey = null;
        if ($sportKey !== null && trim($sportKey) !== '') {
            $normalizedSportKey = $this->teamSportContextService->resolveSportKey($sportKey);
            $query->where($normalizedSportKey !== null
                ? $this->teamSportContextService->buildSportFilterConditions($normalizedSportKey)
                : ['Teams.id' => -1]);
        }

        $normalizedGender = $gender !== null && in_array($gender, ['M', 'F', 'C'], true) ? $gender : null;
        if ($gender !== null && $gender !== '' && $normalizedGender === null) {
            $query->where(['Teams.id' => -1]);
        } elseif ($normalizedGender !== null) {
            $query->where(['Teams.gender' => $normalizedGender]);
        }

        $teams = $query
            ->orderByAsc('Teams.team_name')
            ->all();

        foreach ($teams as $team) {
            $this->teamSportContextService->attachSportContextToTeam($team);
        }

        $sportOptions = $this->teamSportContextService->getSportOptions();
        $genderOptions = Team::getGenderOptions();
        $currentSportKey = $normalizedSportKey ?? '';
        $currentGender = $normalizedGender ?? '';

        return compact('teams', 'sportOptions', 'genderOptions', 'currentSportKey', 'currentGender');
    }

    /**
     * Return view page data.
     *
     * @param string|int $id Team identifier
     * @param mixed $identity Current authenticated identity.
     * @return array{team:\App\Model\Entity\Team}
     */
    public function getViewData(int|string $id, mixed $identity = null): array
    {
        $team = $this->findScopedTeam($id, $identity);
        $this->teamSportContextService->attachSportContextToTeam($team);

        return compact('team');
    }

    /**
     * Return add form data including optional sport pre-selection.
     *
     * @param string|null $sportKey Sport key from query string
     * @param mixed $identity Current authenticated identity.
     * @return array{team:\App\Model\Entity\Team,sports:array<string,string>}
     */
    public function getAddFormData(?string $sportKey = null, mixed $identity = null): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        if ($sportKey !== null && trim($sportKey) !== '') {
            $normalizedKey = strtolower(trim($sportKey));

            if (ctype_digit($normalizedKey)) {
                $resolvedKey = $this->teamSportContextService->resolveSportKeyFromId((int)$normalizedKey);
                if ($resolvedKey !== null) {
                    $normalizedKey = $resolvedKey;
                }
            }

            $team->sport_key = $normalizedKey;

            $resolvedId = $this->teamSportContextService->resolveSportIdFromKey($normalizedKey);
            if ($resolvedId !== null) {
                $team->sport_id = $resolvedId;
            }
        }

        $sports = $this->getRoleSportOptions($identity);

        return compact('team', 'sports');
    }

    /**
     * Create a new team from form data.
     *
     * @param array<string,mixed> $data Request payload
     * @param string|null $sportKey Optional pre-selected sport key
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,team:\App\Model\Entity\Team}
     */
    public function saveNewTeam(array $data, ?string $sportKey = null, mixed $identity = null): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        if ($sportKey !== null && trim($sportKey) !== '' && empty($data['sport_key'])) {
            $data['sport_key'] = strtolower(trim($sportKey));
        }

        $data = $this->normalizeSportData($data);
        $resolvedSportKey = $this->teamSportContextService->resolveSportKey((string)($data['sport_key'] ?? ''));
        if (
            $resolvedSportKey === null
            || !$this->adminDataScopeService->canCreateTeamForSport($identity, $resolvedSportKey)
        ) {
            return ['success' => false, 'team' => $team];
        }

        $team = $this->getTeamsTable()->patchEntity($team, $data);
        $success = (bool)$this->getTeamsTable()->save($team);

        if ($success) {
            $this->teamSportContextService->attachSportContextToTeam($team);
        }

        return compact('success', 'team');
    }

    /**
     * Return edit form data.
     *
     * @param string|int $id Team identifier
     * @param mixed $identity Current authenticated identity.
     * @return array{team:\App\Model\Entity\Team,sports:array<string,string>}
     */
    public function getEditFormData(int|string $id, mixed $identity = null): array
    {
        $team = $this->findScopedTeam($id, $identity);
        $this->teamSportContextService->attachSportContextToTeam($team);
        $currentSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
        $sports = $this->getRoleSportOptions($identity);
        if ($currentSportKey !== null) {
            $allSports = $this->teamSportContextService->getSportOptions();
            if (isset($allSports[$currentSportKey])) {
                $sports[$currentSportKey] = $allSports[$currentSportKey];
            }
        }

        return compact('team', 'sports');
    }

    /**
     * Update an existing team.
     *
     * @param string|int $id Team identifier
     * @param array<string,mixed> $data Request payload
     * @param mixed $identity Current authenticated identity.
     * @return array{success:bool,team:\App\Model\Entity\Team}
     */
    public function saveExistingTeam(int|string $id, array $data, mixed $identity = null): array
    {
        $team = $this->findScopedTeam($id, $identity);
        $currentSportKey = $this->teamSportContextService->resolveSportKeyFromTeam($team);
        $data = $this->normalizeSportData($data);
        $requestedSportKey = $this->teamSportContextService->resolveSportKey(
            (string)($data['sport_key'] ?? $currentSportKey ?? ''),
        );
        if (
            $requestedSportKey === null
            || ($requestedSportKey !== $currentSportKey
                && !$this->adminDataScopeService->canCreateTeamForSport($identity, $requestedSportKey))
        ) {
            return ['success' => false, 'team' => $team];
        }

        $team = $this->getTeamsTable()->patchEntity($team, $data);
        $success = (bool)$this->getTeamsTable()->save($team);

        if ($success) {
            $this->teamSportContextService->attachSportContextToTeam($team);
        }

        return compact('success', 'team');
    }

    /**
     * Delete a team.
     *
     * @param string|int $id Team identifier
     * @param mixed $identity Current authenticated identity
     * @return bool
     */
    public function deleteTeam(int|string $id, mixed $identity = null): bool
    {
        $scoped = $this->rbacPermissionService->scopeQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find(),
            'delete',
            'id',
        );
        $scoped = $this->adminDataScopeService->scopeTeamOwnedQuery($identity, 'Teams', $scoped);
        $team = $scoped->where(['Teams.id' => (int)$id])->first();
        if ($team === null) {
            return false;
        }

        return (bool)$this->getTeamsTable()->delete($team);
    }

    /**
     * Bulk delete teams by identifier list.
     *
     * @param array<mixed> $rawIds Raw identifier list from request
     * @param mixed $identity Current authenticated identity
     * @return int Number of deleted records
     */
    public function bulkDeleteTeams(array $rawIds, mixed $identity = null): int
    {
        $teamIds = $this->sanitizeIdentifierList($rawIds);
        if ($teamIds === []) {
            return 0;
        }

        $allowedQuery = $this->rbacPermissionService->scopeQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find(),
            'delete',
            'id',
        );
        $allowed = $this->adminDataScopeService->scopeTeamOwnedQuery($identity, 'Teams', $allowedQuery)
            ->select(['Teams.id'])
            ->where(['Teams.id IN' => $teamIds])
            ->enableHydration(false)
            ->all()
            ->extract('id')
            ->toList();

        $allowedIds = array_values(array_map('intval', $allowed));
        if ($allowedIds === []) {
            return 0;
        }

        $deletedCount = 0;
        foreach ($allowedIds as $id) {
            try {
                $team = $this->getTeamsTable()->get($id);
                if ($this->getTeamsTable()->delete($team)) {
                    $deletedCount++;
                }
            } catch (RecordNotFoundException $exception) {
                continue;
            }
        }

        return $deletedCount;
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
     * Save a team from popup add form and return JSON-ready payload.
     *
     * @param array<string,mixed> $data Request payload
     * @param mixed $identity Current authenticated identity.
     * @return array<string,mixed>
     */
    public function createTeamFromPopup(array $data, mixed $identity = null): array
    {
        /** @var \App\Model\Entity\Team $team */
        $team = $this->getTeamsTable()->newEmptyEntity();
        $data = $this->normalizeSportData($data);
        $sportKey = $this->teamSportContextService->resolveSportKey((string)($data['sport_key'] ?? ''));
        if ($sportKey === null || !$this->adminDataScopeService->canCreateTeamForSport($identity, $sportKey)) {
            return [
                'success' => false,
                'errors' => ['Your role is not allowed to create a team for this sport.'],
            ];
        }

        $team = $this->getTeamsTable()->patchEntity($team, $data);

        if ($this->getTeamsTable()->save($team)) {
            return [
                'success' => true,
                'message' => 'Team has been added successfully.',
                'newOption' => [
                    'value' => $team->id,
                    'text' => $team->team_name,
                ],
            ];
        }

        return [
            'success' => false,
            'errors' => $this->collectValidationErrors($team) ?: ['Unable to save team. Please try again.'],
        ];
    }

    /**
     * Convert entity validation errors into frontend-friendly strings.
     *
     * @param \Cake\Datasource\EntityInterface $entity Team entity
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
     * @param string|int $id Team identifier.
     * @param mixed $identity Current authenticated identity.
     * @return \App\Model\Entity\Team
     * @throws \Cake\Datasource\Exception\RecordNotFoundException If the team is outside the role scope.
     */
    private function findScopedTeam(int|string $id, mixed $identity): Team
    {
        $team = $this->adminDataScopeService->scopeTeamOwnedQuery(
            $identity,
            'Teams',
            $this->getTeamsTable()->find(),
        )
            ->where(['Teams.id' => (int)$id])
            ->first();

        if (!$team instanceof Team) {
            throw new RecordNotFoundException('Team not found.');
        }

        return $team;
    }

    /**
     * @param mixed $identity Current authenticated identity.
     * @return array<string,string>
     */
    private function getRoleSportOptions(mixed $identity): array
    {
        $options = $this->teamSportContextService->getSportOptions();
        foreach (array_keys($options) as $sportKey) {
            if (!$this->adminDataScopeService->canCreateTeamForSport($identity, $sportKey)) {
                unset($options[$sportKey]);
            }
        }

        return $options;
    }

    /**
     * Normalize sport_id/sport_key payload values for transition compatibility.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function normalizeSportData(array $data): array
    {
        $sportId = isset($data['sport_id']) ? (int)$data['sport_id'] : 0;
        $sportKey = strtolower(trim((string)($data['sport_key'] ?? '')));

        if ($sportKey !== '' && ctype_digit($sportKey)) {
            $resolvedFromNumericKey = $this->teamSportContextService->resolveSportKeyFromId((int)$sportKey);
            if ($resolvedFromNumericKey !== null) {
                $sportKey = $resolvedFromNumericKey;
                $data['sport_key'] = $sportKey;
            }
        }

        if ($sportKey === '' && $sportId > 0) {
            $resolvedKey = $this->teamSportContextService->resolveSportKeyFromId($sportId);
            if ($resolvedKey !== null) {
                $sportKey = $resolvedKey;
                $data['sport_key'] = $sportKey;
            }
        }

        if ($sportKey !== '' && $sportId <= 0) {
            $resolvedId = $this->teamSportContextService->resolveSportIdFromKey($sportKey);
            if ($resolvedId !== null) {
                $data['sport_id'] = $resolvedId;
            }
        }

        return $data;
    }

    /**
     * @return \App\Model\Table\TeamsTable
     */
    private function getTeamsTable(): TeamsTable
    {
        /** @var \App\Model\Table\TeamsTable $table */
        $table = TableRegistry::getTableLocator()->get('Teams');

        return $table;
    }
}
