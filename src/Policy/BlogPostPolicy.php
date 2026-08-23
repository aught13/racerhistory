<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\BlogPost;
use Authorization\IdentityInterface;

class BlogPostPolicy
{
    use RbacPolicyTrait;

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\BlogPost $blogPost
     * @return bool
     */
    public function canIndex(IdentityInterface $identity, BlogPost $blogPost): bool
    {
        return $this->canRbacRead($identity, 'BlogPosts', null, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\BlogPost $blogPost
     * @return bool
     */
    public function canView(IdentityInterface $identity, BlogPost $blogPost): bool
    {
        return $this->canRbacRead($identity, 'BlogPosts', $blogPost, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\BlogPost $blogPost
     * @return bool
     */
    public function canAdd(IdentityInterface $identity, BlogPost $blogPost): bool
    {
        return $this->canRbacCreate($identity, 'BlogPosts');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\BlogPost $blogPost
     * @return bool
     */
    public function canEdit(IdentityInterface $identity, BlogPost $blogPost): bool
    {
        return $this->canRbacUpdate($identity, 'BlogPosts', $blogPost, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\BlogPost $blogPost
     * @return bool
     */
    public function canDelete(IdentityInterface $identity, BlogPost $blogPost): bool
    {
        return $this->canRbacDelete($identity, 'BlogPosts', $blogPost, 'user_id');
    }
}
