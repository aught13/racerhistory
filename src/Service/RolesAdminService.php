<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Permission;
use App\Model\Entity\Role;
use App\Model\Table\PermissionsTable;
use App\Model\Table\RoleDataScopesTable;
use App\Model\Table\RolesTable;
use Cake\ORM\TableRegistry;
use Throwable;

class RolesAdminService
{
    private RolesTable $rolesTable;

    private PermissionsTable $permissionsTable;

    private RoleDataScopesTable $roleDataScopesTable;

    private RbacPermissionService $rbacPermissionService;

    private PublicSportScopeService $publicSportScopeService;

    private AdminDataScopeService $adminDataScopeService;

    /**
     * @param \App\Model\Table\RolesTable|null $rolesTable RBAC roles table.
     * @param \App\Model\Table\PermissionsTable|null $permissionsTable RBAC permissions table.
     * @param \App\Model\Table\RoleDataScopesTable|null $roleDataScopesTable Role data scopes table.
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService Shared RBAC permission resolver.
     * @param \App\Service\AdminDataScopeService|null $adminDataScopeService Role scope resolver.
     */
    public function __construct(
        ?RolesTable $rolesTable = null,
        ?PermissionsTable $permissionsTable = null,
        ?RoleDataScopesTable $roleDataScopesTable = null,
        ?RbacPermissionService $rbacPermissionService = null,
        ?AdminDataScopeService $adminDataScopeService = null,
    ) {
        /** @var \App\Model\Table\RolesTable $resolvedRoles */
        $resolvedRoles = $rolesTable ?? TableRegistry::getTableLocator()->get('Roles');
        /** @var \App\Model\Table\PermissionsTable $resolvedPermissions */
        $resolvedPermissions = $permissionsTable ?? TableRegistry::getTableLocator()->get('Permissions');
        /** @var \App\Model\Table\RoleDataScopesTable $resolvedScopes */
        $resolvedScopes = $roleDataScopesTable ?? TableRegistry::getTableLocator()->get('RoleDataScopes');

        $this->rolesTable = $resolvedRoles;
        $this->permissionsTable = $resolvedPermissions;
        $this->roleDataScopesTable = $resolvedScopes;
        $this->rbacPermissionService = $rbacPermissionService
            ?? new RbacPermissionService($resolvedRoles, $resolvedPermissions);
        $this->publicSportScopeService = new PublicSportScopeService();
        $this->adminDataScopeService = $adminDataScopeService ?? new AdminDataScopeService(
            $resolvedScopes,
            rbacPermissionService: $this->rbacPermissionService,
        );
    }

    /**
     * Build the roles landing-page data.
     *
     * @return array{roles:array<int,\App\Model\Entity\Role>}
     */
    public function getIndexViewData(): array
    {
        $rawRoles = $this->rolesTable->find()
            ->contain(['Permissions'])
            ->orderAsc('Roles.name')
            ->all()
            ->toList();

        $roles = [];
        foreach ($rawRoles as $role) {
            if ($role instanceof Role) {
                $roles[] = $role;
            }
        }

        return ['roles' => $roles];
    }

    /**
     * Build the role select options used by the users admin forms.
     *
     * @return array<int,string>
     */
    public function getRoleOptions(): array
    {
        return $this->rolesTable->find('list', [
            'keyField' => 'id',
            'valueField' => 'name',
        ])->orderAsc('Roles.name')->toArray();
    }

    /**
     * Build the matrix-edit page data for one role.
     *
     * @param int $roleId RBAC role id.
     * @return array{role:\App\Model\Entity\Role,matrix:array<string,array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>,levelOptionsByModel:array<string,array<string,string>>,modelDefinitions:array<string,array{label:string,custom_rules:array<string,string>}>,roleScopeMode:string,selectedSportKeys:array<int,string>,selectedTeamIds:array<int,int>,scopeSportOptions:array<string,string>,scopeTeamOptions:array<int,array{label:string,sportKey:string}>,roleIsGlobalAdmin:bool}
     */
    public function getEditViewData(int $roleId): array
    {
          /** @var \App\Model\Entity\Role $role */
          $role = $this->rolesTable->get($roleId, contain: ['Permissions', 'RoleDataScopes']);

        $matrix = [];
        $modelDefinitions = $this->rbacPermissionService->getModelDefinitions();
        foreach ($modelDefinitions as $modelName => $definition) {
            unset($definition);
            $matrix[$modelName] = $this->rbacPermissionService->buildDefaultPermissionPayload($modelName);
        }

        foreach ((array)$role->permissions as $permission) {
            if (!$permission instanceof Permission) {
                continue;
            }
            $customRules = $permission->custom_rules;
            if (is_string($customRules)) {
                $decoded = json_decode($customRules, true);
                $customRules = is_array($decoded) ? $decoded : [];
            }
            $matrix[(string)$permission->model_name] = [
                'can_create' => (bool)$permission->can_create,
                'can_read' => (string)$permission->can_read,
                'can_update' => (string)$permission->can_update,
                'can_delete' => (string)$permission->can_delete,
                'custom_rules' => is_array($customRules) ? $customRules : [],
            ];
        }

        $levelOptionsByModel = [];
        foreach ($modelDefinitions as $modelName => $definition) {
            unset($definition);
            $levelOptionsByModel[$modelName] = $this->rbacPermissionService->getLevelOptionsForModel($modelName);
        }

        $roleIsGlobalAdmin = strtolower((string)$role->name) === 'admin';
        $roleScopeMode = 'selected';
        $selectedSportKeys = [];
        $selectedTeamIds = [];
        foreach ((array)$role->role_data_scopes as $scope) {
            $scopeType = (string)$scope->scope_type;
            if ($scopeType === 'all') {
                $roleScopeMode = 'all';
            } elseif ($scopeType === 'sport' && !empty($scope->sport_key)) {
                $selectedSportKeys[] = strtolower((string)$scope->sport_key);
            } elseif ($scopeType === 'team' && (int)$scope->team_id > 0) {
                $selectedTeamIds[] = (int)$scope->team_id;
            }
        }

        $scopeSportOptions = $this->publicSportScopeService->getAvailableSportOptions();
        $scopeTeamOptions = $this->publicSportScopeService->getTeamOptions();

        return compact(
            'role',
            'matrix',
            'levelOptionsByModel',
            'modelDefinitions',
            'roleScopeMode',
            'selectedSportKeys',
            'selectedTeamIds',
            'scopeSportOptions',
            'scopeTeamOptions',
            'roleIsGlobalAdmin',
        );
    }

    /**
     * Persist the data scope grants for one role.
     *
     * @param int $roleId RBAC role id.
     * @param array<string,mixed> $submittedScope Submitted scope form values.
     * @return bool
     */
    public function saveDataScope(int $roleId, array $submittedScope): bool
    {
        /** @var \App\Model\Entity\Role $role */
        $role = $this->rolesTable->get($roleId);
        if (strtolower((string)$role->name) === 'admin') {
            return true;
        }

        $mode = $submittedScope['mode'] ?? null;
        if (!is_string($mode) || !in_array($mode, ['all', 'selected'], true)) {
            return false;
        }

        $scopeRows = [];
        if ($mode === 'all') {
            $scopeRows[] = ['scope_type' => 'all'];
        } else {
            $submittedSportKeys = $this->normalizeSubmittedList($submittedScope['sport_keys'] ?? []);
            $submittedTeamIds = $this->normalizeSubmittedList($submittedScope['team_ids'] ?? []);
            if ($submittedSportKeys === null || $submittedTeamIds === null) {
                return false;
            }

            $sportOptions = $this->publicSportScopeService->getAvailableSportOptions();
            foreach ($submittedSportKeys as $sportKey) {
                if (!array_key_exists($sportKey, $sportOptions)) {
                    return false;
                }
                $scopeRows[] = ['scope_type' => 'sport', 'sport_key' => $sportKey];
            }

            $teamOptions = $this->publicSportScopeService->getTeamOptions();
            foreach ($submittedTeamIds as $teamId) {
                if (!ctype_digit($teamId) || !array_key_exists((int)$teamId, $teamOptions)) {
                    return false;
                }
                $scopeRows[] = ['scope_type' => 'team', 'team_id' => (int)$teamId];
            }
        }

        /** @var \Cake\Database\Connection $connection */
        $connection = $this->roleDataScopesTable->getConnection();
        try {
            return (bool)$connection->transactional(function () use ($roleId, $scopeRows): bool {
                $this->roleDataScopesTable->deleteAll(['RoleDataScopes.role_id' => $roleId]);
                foreach ($scopeRows as $scopeRow) {
                    $scope = $this->roleDataScopesTable->newEntity([
                        'role_id' => $roleId,
                    ] + $scopeRow);
                    if ($this->roleDataScopesTable->save($scope) === false) {
                        return false;
                    }
                }

                $this->adminDataScopeService->clearRoleScopeCache($roleId);

                return true;
            });
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Persist a full role-permission matrix submission.
     *
     * @param int $roleId RBAC role id.
     * @param array<string,mixed> $submittedPermissions Submitted matrix payload.
     * @return bool
     */
    public function savePermissions(int $roleId, array $submittedPermissions): bool
    {
        $allSaved = true;
        $definitions = $this->rbacPermissionService->getModelDefinitions();

        foreach ($definitions as $modelName => $definition) {
            $submitted = (array)($submittedPermissions[$modelName] ?? []);
            $customRules = [];
            foreach ($definition['custom_rules'] as $ruleKey => $ruleLabel) {
                unset($ruleLabel);
                $customRules[$ruleKey] = !empty($submitted['custom_rules'][$ruleKey]);
            }

            $payload = [
                'role_id' => $roleId,
                'model_name' => $modelName,
                'can_create' => !empty($submitted['can_create']),
                'can_read' => $this->normalizeLevel(
                    $modelName,
                    (string)($submitted['can_read'] ?? RbacPermissionService::LEVEL_NONE),
                ),
                'can_update' => $this->normalizeLevel(
                    $modelName,
                    (string)($submitted['can_update'] ?? RbacPermissionService::LEVEL_NONE),
                ),
                'can_delete' => $this->normalizeLevel(
                    $modelName,
                    (string)($submitted['can_delete'] ?? RbacPermissionService::LEVEL_NONE),
                ),
                'custom_rules' => $customRules === [] ? null : json_encode($customRules),
            ];

            $permission = $this->permissionsTable->find()
                ->where(['role_id' => $roleId, 'model_name' => $modelName])
                ->first();

            if (!$permission instanceof Permission) {
                $permission = $this->permissionsTable->newEmptyEntity();
            }

            $permission = $this->permissionsTable->patchEntity($permission, $payload);
            $saved = $this->permissionsTable->save($permission);
            if ($saved === false) {
                $allSaved = false;
            }
        }

        $this->rbacPermissionService->clearRoleCache($roleId);

        return $allSaved;
    }

    /**
     * Normalize one matrix radio value onto the accepted RBAC levels.
     *
     * @param string $modelName Canonical RBAC model name.
     * @param string $level Submitted matrix level.
     * @return string
     */
    private function normalizeLevel(string $modelName, string $level): string
    {
        return $this->rbacPermissionService->normalizeLevelForModel($modelName, $level);
    }

    /**
     * Normalize a submitted list of scalar keys without accepting nested values.
     *
     * @param mixed $values Submitted list.
     * @return list<string>|null
     */
    private function normalizeSubmittedList(mixed $values): ?array
    {
        if ($values === null || $values === '') {
            return [];
        }
        if (!is_array($values)) {
            return null;
        }

        $normalized = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                return null;
            }
            $value = trim((string)$value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }
}
