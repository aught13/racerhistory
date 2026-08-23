<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Image;
use Authorization\IdentityInterface;

class ImagePolicy
{
    use RbacPolicyTrait;

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\Image $image
     * @return bool
     */
    public function canIndex(IdentityInterface $identity, Image $image): bool
    {
        return $this->canRbacRead($identity, 'Images', null, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\Image $image
     * @return bool
     */
    public function canView(IdentityInterface $identity, Image $image): bool
    {
        return $this->canRbacRead($identity, 'Images', $image, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\Image $image
     * @return bool
     */
    public function canAdd(IdentityInterface $identity, Image $image): bool
    {
        return $this->canRbacCreate($identity, 'Images');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\Image $image
     * @return bool
     */
    public function canEdit(IdentityInterface $identity, Image $image): bool
    {
        return $this->canRbacUpdate($identity, 'Images', $image, 'user_id');
    }

    /**
     * @param \Authorization\IdentityInterface $identity
     * @param \App\Model\Entity\Image $image
     * @return bool
     */
    public function canDelete(IdentityInterface $identity, Image $image): bool
    {
        return $this->canRbacDelete($identity, 'Images', $image, 'user_id');
    }
}
