<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Permission;
use App\Model\Entity\Role;
use App\Model\Table\PermissionsTable;
use App\Model\Table\RolesTable;
use Cake\ORM\TableRegistry;

class RolesAdminService
{
    private RolesTable $rolesTable;

    private PermissionsTable $permissionsTable;

    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Model\Table\RolesTable|null $rolesTable RBAC roles table.
     * @param \App\Model\Table\PermissionsTable|null $permissionsTable RBAC permissions table.
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService Shared RBAC permission resolver.
     */
    public function __construct(
        ?RolesTable $rolesTable = null,
        ?PermissionsTable $permissionsTable = null,
        ?RbacPermissionService $rbacPermissionService = null,
    ) {
        /** @var \App\Model\Table\RolesTable $resolvedRoles */
        $resolvedRoles = $rolesTable ?? TableRegistry::getTableLocator()->get('Roles');
        /** @var \App\Model\Table\PermissionsTable $resolvedPermissions */
        $resolvedPermissions = $permissionsTable ?? TableRegistry::getTableLocator()->get('Permissions');

        $this->rolesTable = $resolvedRoles;
        $this->permissionsTable = $resolvedPermissions;
        $this->rbacPermissionService = $rbacPermissionService
            ?? new RbacPermissionService($resolvedRoles, $resolvedPermissions);
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
     * @return array{role:\App\Model\Entity\Role,matrix:array<string,array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>,levelOptionsByModel:array<string,array<string,string>>,modelDefinitions:array<string,array{label:string,custom_rules:array<string,string>}>}
     */
    public function getEditViewData(int $roleId): array
    {
        /** @var \App\Model\Entity\Role $role */
        $role = $this->rolesTable->get($roleId, contain: ['Permissions']);

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

        return compact('role', 'matrix', 'levelOptionsByModel', 'modelDefinitions');
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
}
