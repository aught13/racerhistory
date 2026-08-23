<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\ORM\Query\SelectQuery;

class BlogPostsTablePolicy
{
    use RbacPolicyTrait;

    /**
     * Scope blog-post index queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'BlogPosts', 'read', 'user_id');
    }

    /**
     * Scope blog-post update queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeUpdate(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'BlogPosts', 'update', 'user_id');
    }

    /**
     * Scope blog-post delete queries for the current identity.
     *
     * @param \Authorization\IdentityInterface|null $identity Current identity.
     * @param \Cake\ORM\Query\SelectQuery $query Query to scope.
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeDelete(?IdentityInterface $identity, SelectQuery $query): SelectQuery
    {
        return $this->scopeRbacQuery($identity, $query, 'BlogPosts', 'delete', 'user_id');
    }
}
