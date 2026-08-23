<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

class ImagesTablePolicy
{
    use RbacPolicyTrait;

    /**
     * Scope image index queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'Images', 'read', 'user_id');
    }

    /**
     * Scope image update queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeUpdate(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'Images', 'update', 'user_id');
    }

    /**
     * Scope image delete queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeDelete(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'Images', 'delete', 'user_id');
    }
}
