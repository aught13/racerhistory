<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Permission;
use App\Model\Entity\Role;
use App\Model\Table\PermissionsTable;
use App\Model\Table\RolesTable;
use ArrayAccess;
use Authorization\IdentityInterface;
use Cake\Cache\Cache;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Throwable;

class RbacPermissionService
{
    public const LEVEL_NONE = 'none';
    public const LEVEL_OWN = 'own';
    public const LEVEL_ALL = 'all';

    public const BLOG_RULE_CAN_PIN = 'can_pin_posts';
    public const BLOG_RULE_CAN_MANAGE_PIN_SETTINGS = 'can_manage_pin_settings';

    /**
     * Models where the `own` level is meaningful for read/update/delete.
     *
     * @var list<string>
     */
    private const MODELS_SUPPORTING_OWN = [
        'BlogPosts',
        'Images',
        'Users',
    ];

    /**
     * @var array<string, array{label:string,custom_rules:array<string,string>}>
     */
    public const MODEL_DEFINITIONS = [
        'BlogPosts' => [
            'label' => 'Blog Posts',
            'custom_rules' => [
                self::BLOG_RULE_CAN_PIN => 'Can Pin Posts',
                self::BLOG_RULE_CAN_MANAGE_PIN_SETTINGS => 'Can Modify Pin Rank / Expiration',
            ],
        ],
        'Images' => ['label' => 'Images', 'custom_rules' => []],
        'Games' => ['label' => 'Games', 'custom_rules' => []],
        'TeamSeasons' => ['label' => 'Team Seasons', 'custom_rules' => []],
        'TeamSeasonRosters' => ['label' => 'Roster Bindings', 'custom_rules' => []],
        'Persons' => ['label' => 'People', 'custom_rules' => []],
        'Opponents' => ['label' => 'Opponents', 'custom_rules' => []],
        'Places' => ['label' => 'Places', 'custom_rules' => []],
        'Sites' => ['label' => 'Sites', 'custom_rules' => []],
        'GameTypes' => ['label' => 'Game Types', 'custom_rules' => []],
        'Teams' => ['label' => 'Teams', 'custom_rules' => []],
        'Seasons' => ['label' => 'Seasons', 'custom_rules' => []],
        'Users' => ['label' => 'Users', 'custom_rules' => []],
        'SiteOptions' => ['label' => 'Site Settings', 'custom_rules' => []],
        'Roles' => ['label' => 'Roles', 'custom_rules' => []],
    ];

    /**
     * @var array<string, string>
     */
    private const CONTROLLER_MODEL_MAP = [
        'Blog' => 'BlogPosts',
        'BlogPosts' => 'BlogPosts',
        'Images' => 'Images',
        'Games' => 'Games',
        'GameTypes' => 'GameTypes',
        'Opponents' => 'Opponents',
        'Persons' => 'Persons',
        'Places' => 'Places',
        'Sites' => 'Sites',
        'Teams' => 'Teams',
        'Seasons' => 'Seasons',
        'TeamSeasons' => 'TeamSeasons',
        'TeamSeasonRosters' => 'TeamSeasonRosters',
        'Users' => 'Users',
        'SiteOptions' => 'SiteOptions',
        'Roles' => 'Roles',
        'Tags' => 'BlogPosts',
        'TagLookups' => 'BlogPosts',
        'StatBasketGameBox' => 'Games',
        'StatBasketGamePerson' => 'Games',
        'StatBasketGameTeam' => 'Games',
        'StatBasketGameOpponent' => 'Games',
        'StatBasketSeasonPerson' => 'TeamSeasons',
        'StatBasketSeasonTeam' => 'TeamSeasons',
        'StatBasketSeasonOpponent' => 'TeamSeasons',
    ];

    /**
     * @var array<string, string>
     */
    private const ACTION_ABILITY_MAP = [
        'index' => 'read',
        'view' => 'read',
        'datatables' => 'read',
        'ajaxList' => 'read',
        'ajaxGameEavMeta' => 'read',
        'ajaxSitesByPlace' => 'read',
        'ajaxSearch' => 'read',
        'countriesLookup' => 'read',
        'browse' => 'read',
        'sportsConfigs' => 'read',
        'persons' => 'read',
        'games' => 'read',
        'opponents' => 'read',
        'sites' => 'read',
        'rosters' => 'read',
        'modal' => 'read',
        'add' => 'create',
        'create' => 'create',
        'ajaxAdd' => 'create',
        'bulkAdd' => 'create',
        'upload' => 'create',
        'bulkUpload' => 'create',
        'uploadForm' => 'create',
        'bulkUploadForm' => 'create',
        'addResults' => 'update',
        'gameBox' => 'update',
        'gameBoxPeriods' => 'update',
        'edit' => 'update',
        'bulkEdit' => 'update',
        'manage' => 'update',
        'approve' => 'update',
        'bulkActivate' => 'update',
        'toggleRegistration' => 'update',
        'clearCache' => 'update',
        'toggleApproval' => 'update',
        'apply' => 'update',
        'editSportConfigs' => 'update',
        'addSportConfig' => 'update',
        'resetSportConfigs' => 'update',
        'writeAdsTxt' => 'update',
        'tags' => 'update',
        'manipulate' => 'update',
        'cropThumb' => 'update',
        'cropHero' => 'update',
        'delete' => 'delete',
        'deleteConfirm' => 'delete',
        'bulk' => 'delete',
        'bulkDelete' => 'delete',
        'deleteSportConfig' => 'delete',
    ];

    private RolesTable $rolesTable;

    private PermissionsTable $permissionsTable;

    /**
     * @param \App\Model\Table\RolesTable|null $rolesTable RBAC roles table.
     * @param \App\Model\Table\PermissionsTable|null $permissionsTable RBAC permissions table.
     */
    public function __construct(?RolesTable $rolesTable = null, ?PermissionsTable $permissionsTable = null)
    {
        /** @var \App\Model\Table\RolesTable $resolvedRoles */
        $resolvedRoles = $rolesTable ?? TableRegistry::getTableLocator()->get('Roles');
        /** @var \App\Model\Table\PermissionsTable $resolvedPermissions */
        $resolvedPermissions = $permissionsTable ?? TableRegistry::getTableLocator()->get('Permissions');

        $this->rolesTable = $resolvedRoles;
        $this->permissionsTable = $resolvedPermissions;
    }

    /**
     * @return array<string, array{label:string,custom_rules:array<string,string>}>
     */
    public function getModelDefinitions(): array
    {
        return self::MODEL_DEFINITIONS;
    }

    /**
     * Determine whether a model supports the `own` level.
     *
     * @param string $modelName Canonical RBAC model name.
     * @return bool
     */
    public function supportsOwnLevel(string $modelName): bool
    {
        return in_array($modelName, self::MODELS_SUPPORTING_OWN, true);
    }

    /**
     * Return the selectable level labels for one model.
     *
     * @param string $modelName Canonical RBAC model name.
     * @return array<string,string>
     */
    public function getLevelOptionsForModel(string $modelName): array
    {
        if ($this->supportsOwnLevel($modelName)) {
            return [
                self::LEVEL_NONE => 'None',
                self::LEVEL_OWN => 'Own',
                self::LEVEL_ALL => 'All',
            ];
        }

        return [
            self::LEVEL_NONE => 'No',
            self::LEVEL_ALL => 'Yes',
        ];
    }

    /**
     * Normalize a submitted level for a specific model.
     *
     * @param string $modelName Canonical RBAC model name.
     * @param string $level Submitted level string.
     * @return string
     */
    public function normalizeLevelForModel(string $modelName, string $level): string
    {
        if (!in_array($level, [self::LEVEL_NONE, self::LEVEL_OWN, self::LEVEL_ALL], true)) {
            return self::LEVEL_NONE;
        }

        if ($level === self::LEVEL_OWN && !$this->supportsOwnLevel($modelName)) {
            return self::LEVEL_NONE;
        }

        return $level;
    }

    /**
     * Determine whether the current identity is an RBAC administrator.
     *
     * @param mixed $identity Current authenticated identity.
     * @return bool
     */
    public function isAdmin(mixed $identity): bool
    {
        if ($this->extractIdentityField($identity, 'is_superuser') === true) {
            return true;
        }

        $legacyRole = strtolower((string)($this->extractIdentityField($identity, 'role') ?? ''));
        if ($legacyRole === 'admin') {
            return true;
        }

        $roleName = strtolower((string)($this->getRoleNameForIdentity($identity) ?? ''));

        return $roleName === 'admin';
    }

    /**
     * Determine whether the current identity is active enough to enter admin flows.
     *
     * @param mixed $identity Current authenticated identity.
     * @return bool
     */
    public function isActiveIdentity(mixed $identity): bool
    {
        if ($identity === null) {
            return false;
        }

        $status = $this->extractIdentityField($identity, 'status');
        $active = $this->extractIdentityField($identity, 'active');

        return $status === 'active' || $active === true || $active === 1;
    }

    /**
     * Determine whether the identity should be allowed into the admin shell at all.
     *
     * @param mixed $identity Current authenticated identity.
     * @return bool
     */
    public function hasAnyAdminAccess(mixed $identity): bool
    {
        if (!$this->isActiveIdentity($identity)) {
            return false;
        }

        if ($this->isAdmin($identity)) {
            return true;
        }

        return $this->getPermissionMapForIdentity($identity) !== [];
    }

    /**
     * Resolve whether an identity can hit a specific admin controller/action route.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $controller Admin controller name.
     * @param string $action Admin action name.
     * @param string|null $path Request path for debug-kit blocking.
     * @return bool
     */
    public function canAccessAdminRequest(
        mixed $identity,
        string $controller,
        string $action,
        ?string $path = null,
    ): bool {
        if ($path !== null && str_starts_with($path, '/debug-kit')) {
            return false;
        }

        if (!$this->isActiveIdentity($identity)) {
            return false;
        }

        if ($this->isAdmin($identity)) {
            return true;
        }

        if ($controller === 'Dashboard') {
            return $this->hasAnyAdminAccess($identity);
        }

        $modelName = $this->resolveControllerModelName($controller);
        $ability = $this->resolveAbilityForAction($action);
        if ($modelName === null || $ability === null) {
            return false;
        }

        return $this->can($identity, $modelName, $ability);
    }

    /**
     * Evaluate an RBAC ability against a model, optionally against a concrete resource.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Canonical RBAC model name.
     * @param string $ability Ability name: create, read, update, or delete.
     * @param mixed $resource Optional resource for own/all checks.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    public function can(
        mixed $identity,
        string $modelName,
        string $ability,
        mixed $resource = null,
        string $ownerField = 'user_id',
    ): bool {
        if (!$this->isActiveIdentity($identity)) {
            return false;
        }

        if ($this->isAdmin($identity)) {
            return true;
        }

        $permission = $this->getPermissionForIdentity($identity, $modelName);
        if ($ability === 'create') {
            return (bool)($permission['can_create'] ?? false);
        }

        $level = match ($ability) {
            'read' => (string)($permission['can_read'] ?? self::LEVEL_NONE),
            'update' => (string)($permission['can_update'] ?? self::LEVEL_NONE),
            'delete' => (string)($permission['can_delete'] ?? self::LEVEL_NONE),
            default => self::LEVEL_NONE,
        };

        return $this->evaluateLevel($identity, $level, $resource, $ownerField);
    }

    /**
     * Resolve a custom JSON rule for a model permission row.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Canonical RBAC model name.
     * @param string $ruleKey Custom rule key.
     * @return bool
     */
    public function allowsCustomRule(mixed $identity, string $modelName, string $ruleKey): bool
    {
        if (!$this->isActiveIdentity($identity)) {
            return false;
        }

        if ($this->isAdmin($identity)) {
            return true;
        }

        $permission = $this->getPermissionForIdentity($identity, $modelName);
        $rules = $permission['custom_rules'] ?? [];

        return !empty($rules[$ruleKey]);
    }

    /**
     * Apply the RBAC scope for a read/update/delete query.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Canonical RBAC model name.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @param string $ability Ability name: read, update, or delete.
     * @param string $ownerField Ownership field on the resource.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeQuery(
        mixed $identity,
        string $modelName,
        SelectQuery $query,
        string $ability = 'read',
        string $ownerField = 'user_id',
    ): SelectQuery {
        if ($this->isAdmin($identity)) {
            return $query;
        }

        $permission = $this->getPermissionForIdentity($identity, $modelName);
        $level = match ($ability) {
            'update' => (string)($permission['can_update'] ?? self::LEVEL_NONE),
            'delete' => (string)($permission['can_delete'] ?? self::LEVEL_NONE),
            default => (string)($permission['can_read'] ?? self::LEVEL_NONE),
        };

        if ($level === self::LEVEL_ALL) {
            return $query;
        }

        if ($level === self::LEVEL_NONE) {
            return $query->where(['1 = 0']);
        }

        $ownerId = $this->extractIdentityField($identity, 'id');
        if (!$ownerId) {
            return $query->where(['1 = 0']);
        }

        $alias = $query->getRepository()->getAlias();

        return $query->where([$alias . '.' . $ownerField => (int)$ownerId]);
    }

    /**
     * Resolve one model permission payload for an identity.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $modelName Canonical RBAC model name.
     * @return array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}
     */
    public function getPermissionForIdentity(mixed $identity, string $modelName): array
    {
        $permissions = $this->getPermissionMapForIdentity($identity);

        return $permissions[$modelName] ?? $this->buildDefaultPermissionPayload($modelName);
    }

    /**
     * Resolve the complete permission map for an identity.
     *
     * @param mixed $identity Current authenticated identity.
     * @return array<string, array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>
     */
    public function getPermissionMapForIdentity(mixed $identity): array
    {
        $roleId = $this->getRoleIdForIdentity($identity);
        if ($roleId === null || $roleId <= 0) {
            return [];
        }

        return $this->getPermissionMapForRoleId($roleId);
    }

    /**
     * Resolve the complete permission map for one role id.
     *
     * @param int $roleId RBAC role id.
     * @return array<string, array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>
     */
    public function getPermissionMapForRoleId(int $roleId): array
    {
        $cacheKey = 'rbac_permissions_role_' . $roleId;
        $cached = Cache::read($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $map = [];
        try {
            $rows = $this->permissionsTable->find()
                ->where(['role_id' => $roleId])
                ->all();

            foreach ($rows as $row) {
                if (!$row instanceof Permission) {
                    continue;
                }
                $modelName = (string)$row->model_name;
                $customRules = $row->custom_rules;
                if (is_string($customRules)) {
                    $decoded = json_decode($customRules, true);
                    $customRules = is_array($decoded) ? $decoded : [];
                }
                $map[$modelName] = [
                    'can_create' => (bool)$row->can_create,
                    'can_read' => $this->normalizeLevelForModel($modelName, (string)$row->can_read),
                    'can_update' => $this->normalizeLevelForModel($modelName, (string)$row->can_update),
                    'can_delete' => $this->normalizeLevelForModel($modelName, (string)$row->can_delete),
                    'custom_rules' => is_array($customRules) ? $customRules : [],
                ];
            }
        } catch (Throwable) {
            return [];
        }

        Cache::write($cacheKey, $map);

        return $map;
    }

    /**
     * Drop the cached permission map for one role.
     *
     * @param int $roleId RBAC role id.
     */
    public function clearRoleCache(int $roleId): void
    {
        Cache::delete('rbac_permissions_role_' . $roleId);
    }

    /**
     * Resolve the RBAC role id for the current identity.
     *
     * @param mixed $identity Current authenticated identity.
     * @return int|null
     */
    public function getRoleIdForIdentity(mixed $identity): ?int
    {
        $roleId = $this->extractIdentityField($identity, 'role_id');
        if (is_numeric($roleId) && (int)$roleId > 0) {
            return (int)$roleId;
        }

        $legacyRole = $this->extractIdentityField($identity, 'role');
        if (!is_string($legacyRole) || $legacyRole === '') {
            return null;
        }

        return $this->findRoleIdByName($legacyRole);
    }

    /**
     * Resolve the normalized RBAC role name for the current identity.
     *
     * @param mixed $identity Current authenticated identity.
     * @return string|null
     */
    public function getRoleNameForIdentity(mixed $identity): ?string
    {
        $roleName = $this->extractIdentityField($identity, 'role');
        if (is_string($roleName) && $roleName !== '') {
            return $this->normalizeLegacyRoleName($roleName);
        }

        $roleId = $this->extractIdentityField($identity, 'role_id');
        if (!is_numeric($roleId) || (int)$roleId <= 0) {
            return null;
        }

        try {
            $role = $this->rolesTable->find()->select(['name'])->where(['id' => (int)$roleId])->first();

            return $role instanceof Role ? strtolower((string)$role->name) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Build the default deny-all permission payload for a matrix row.
     *
     * @param string $modelName Canonical RBAC model name.
     * @return array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}
     */
    public function buildDefaultPermissionPayload(string $modelName): array
    {
        $customRules = [];
        foreach (self::MODEL_DEFINITIONS[$modelName]['custom_rules'] ?? [] as $key => $label) {
            unset($label);
            $customRules[$key] = false;
        }

        return [
            'can_create' => false,
            'can_read' => self::LEVEL_NONE,
            'can_update' => self::LEVEL_NONE,
            'can_delete' => self::LEVEL_NONE,
            'custom_rules' => $customRules,
        ];
    }

    /**
     * Map an admin controller name to its canonical RBAC model name.
     *
     * @param string $controller Admin controller name.
     * @return string|null
     */
    private function resolveControllerModelName(string $controller): ?string
    {
        return self::CONTROLLER_MODEL_MAP[$controller] ?? null;
    }

    /**
     * Map an admin action name to a CRUD-style RBAC ability.
     *
     * @param string $action Admin action name.
     * @return string|null
     */
    private function resolveAbilityForAction(string $action): ?string
    {
        return self::ACTION_ABILITY_MAP[$action] ?? null;
    }

    /**
     * Evaluate an own/all/none access level against an optional resource.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $level RBAC level: none, own, or all.
     * @param mixed $resource Optional concrete resource.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    private function evaluateLevel(mixed $identity, string $level, mixed $resource, string $ownerField): bool
    {
        if ($level === self::LEVEL_ALL) {
            return true;
        }

        if ($level === self::LEVEL_NONE) {
            return false;
        }

        if ($resource === null) {
            return true;
        }

        return $this->ownsResource($identity, $resource, $ownerField);
    }

    /**
     * Determine whether the provided resource is owned by the current identity.
     *
     * @param mixed $identity Current authenticated identity.
     * @param mixed $resource Concrete resource.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    private function ownsResource(mixed $identity, mixed $resource, string $ownerField): bool
    {
        $identityId = $this->extractIdentityField($identity, 'id');
        $ownerId = $this->extractIdentityField($resource, $ownerField);

        return is_numeric($identityId) && is_numeric($ownerId) && (int)$identityId === (int)$ownerId;
    }

    /**
     * Resolve a role id from a legacy or normalized role name.
     *
     * @param string $roleName Legacy or normalized role name.
     * @return int|null
     */
    private function findRoleIdByName(string $roleName): ?int
    {
        $normalized = $this->normalizeLegacyRoleName($roleName);
        $cacheKey = 'rbac_role_id_' . $normalized;
        $cached = Cache::read($cacheKey);
        if (is_numeric($cached)) {
            return (int)$cached;
        }

        try {
            $role = $this->rolesTable->find()->select(['id', 'name'])->all();
            foreach ($role as $candidate) {
                if (!$candidate instanceof Role) {
                    continue;
                }
                if (strtolower((string)$candidate->name) === $normalized) {
                    Cache::write($cacheKey, (int)$candidate->id);

                    return (int)$candidate->id;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Normalize legacy role strings onto the new RBAC role names.
     *
     * @param string $roleName Legacy or normalized role name.
     * @return string
     */
    private function normalizeLegacyRoleName(string $roleName): string
    {
        return match (strtolower(trim($roleName))) {
            'author' => 'blogger',
            default => strtolower(trim($roleName)),
        };
    }

    /**
     * Extract a field from arrays, entities, decorators, or lightweight identity data.
     *
     * @param mixed $data Identity or entity-like source.
     * @param string $field Field name to extract.
     * @return mixed
     */
    private function extractIdentityField(mixed $data, string $field): mixed
    {
        if ($data instanceof IdentityInterface) {
            $data = $data->getOriginalData();
        }

        if (is_array($data)) {
            return $data[$field] ?? null;
        }

        if ($data instanceof ArrayAccess && isset($data[$field])) {
            return $data[$field];
        }

        if (is_object($data)) {
            if (method_exists($data, 'get')) {
                try {
                    return $data->get($field);
                } catch (Throwable) {
                    // Fall through to property/method access.
                }
            }

            if (property_exists($data, $field)) {
                return $data->{$field};
            }

            $accessor = 'get' . ucfirst($field);
            if (method_exists($data, $accessor)) {
                return $data->{$accessor}();
            }
        }

        return null;
    }
}
