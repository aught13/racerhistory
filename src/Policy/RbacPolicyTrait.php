<?php
declare(strict_types=1);

namespace App\Policy;

use App\Service\RbacPermissionService;
use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

trait RbacPolicyTrait
{
    private ?RbacPermissionService $rbacPermissionService = null;

    /**
     * Lazily resolve the shared RBAC permission service.
     */
    protected function rbacPermissionService(): RbacPermissionService
    {
        if ($this->rbacPermissionService === null) {
            $this->rbacPermissionService = new RbacPermissionService();
        }

        return $this->rbacPermissionService;
    }

    /**
     * Evaluate RBAC create permission for a model.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param string $modelName Canonical RBAC model name.
     * @return bool
     */
    protected function canRbacCreate(?IdentityInterface $identity, string $modelName): bool
    {
        return $this->rbacPermissionService()->can($identity, $modelName, 'create');
    }

    /**
     * Evaluate RBAC read permission for a model/resource pair.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param string $modelName Canonical RBAC model name.
     * @param mixed $resource Optional concrete resource.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    protected function canRbacRead(
        ?IdentityInterface $identity,
        string $modelName,
        mixed $resource,
        string $ownerField = 'user_id',
    ): bool {
        return $this->rbacPermissionService()->can($identity, $modelName, 'read', $resource, $ownerField);
    }

    /**
     * Evaluate RBAC update permission for a model/resource pair.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param string $modelName Canonical RBAC model name.
     * @param mixed $resource Optional concrete resource.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    protected function canRbacUpdate(
        ?IdentityInterface $identity,
        string $modelName,
        mixed $resource,
        string $ownerField = 'user_id',
    ): bool {
        return $this->rbacPermissionService()->can($identity, $modelName, 'update', $resource, $ownerField);
    }

    /**
     * Evaluate RBAC delete permission for a model/resource pair.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param string $modelName Canonical RBAC model name.
     * @param mixed $resource Optional concrete resource.
     * @param string $ownerField Ownership field on the resource.
     * @return bool
     */
    protected function canRbacDelete(
        ?IdentityInterface $identity,
        string $modelName,
        mixed $resource,
        string $ownerField = 'user_id',
    ): bool {
        return $this->rbacPermissionService()->can($identity, $modelName, 'delete', $resource, $ownerField);
    }

    /**
     * Evaluate one RBAC custom rule flag.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param string $modelName Canonical RBAC model name.
     * @param string $ruleKey Custom rule key.
     * @return bool
     */
    protected function allowsRbacCustomRule(?IdentityInterface $identity, string $modelName, string $ruleKey): bool
    {
        return $this->rbacPermissionService()->allowsCustomRule($identity, $modelName, $ruleKey);
    }

    /**
     * Apply the RBAC query scope for a repository query.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @param string $modelName Canonical RBAC model name.
     * @param string $ability Ability name: read, update, or delete.
     * @param string $ownerField Ownership field on the resource.
     * @return \Cake\ORM\Query\SelectQuery
     */
    protected function scopeRbacQuery(
        ?IdentityInterface $identity,
        SelectQuery $query,
        string $modelName,
        string $ability = 'read',
        string $ownerField = 'user_id',
    ): SelectQuery {
        return $this->rbacPermissionService()->scopeQuery($identity, $modelName, $query, $ability, $ownerField);
    }
}
