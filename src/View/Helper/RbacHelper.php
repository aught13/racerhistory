<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\RbacPermissionService;
use Cake\View\Helper;

class RbacHelper extends Helper
{
    private ?RbacPermissionService $rbacPermissionService = null;

    /**
     * @var array<string, array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>|null
     */
    private ?array $permissionMap = null;

    private ?bool $isAdmin = null;

    /**
     * Resolve whether current identity can perform a model ability.
     *
     * @param string $modelName Canonical RBAC model name.
     * @param string $ability Ability: create, read, update, or delete.
     * @param mixed $resource Optional resource for own/all checks.
     * @param string $ownerField Ownership field for own checks.
     * @return bool
     */
    public function can(
        string $modelName,
        string $ability = 'read',
        mixed $resource = null,
        string $ownerField = 'user_id',
    ): bool {
        return $this->service()->can($this->identity(), $modelName, $ability, $resource, $ownerField);
    }

    /**
     * Resolve whether current identity is RBAC admin/superuser.
     */
    public function isAdmin(): bool
    {
        if ($this->isAdmin === null) {
            $this->isAdmin = $this->service()->isAdmin($this->identity());
        }

        return $this->isAdmin;
    }

    /**
     * Expose full RBAC permission map for current identity.
     *
     * @return array<string, array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>
     */
    public function permissionMap(): array
    {
        if ($this->permissionMap === null) {
            $this->permissionMap = $this->service()->getPermissionMapForIdentity($this->identity());
        }

        return $this->permissionMap;
    }

    /**
     * Payload consumed by admin UI guards.
     *
     * @return array{isAdmin:bool,permissions:array<string,array{can_create:bool,can_read:string,can_update:string,can_delete:string,custom_rules:array<string,mixed>}>}
     */
    public function uiPayload(): array
    {
        return [
            'isAdmin' => $this->isAdmin(),
            'permissions' => $this->permissionMap(),
        ];
    }

    /**
     * Resolve current authenticated identity from request attributes.
     */
    private function identity(): mixed
    {
        return $this->getView()->getRequest()->getAttribute('identity');
    }

    /**
     * Lazily resolve RBAC service instance.
     */
    private function service(): RbacPermissionService
    {
        if ($this->rbacPermissionService === null) {
            $this->rbacPermissionService = new RbacPermissionService();
        }

        return $this->rbacPermissionService;
    }
}
