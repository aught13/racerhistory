<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use ArrayAccess;
use Authorization\IdentityInterface;
use Throwable;

/**
 * User Policy
 *
 * Policy for User entity authorization checks.
 * Handles permissions for viewing, editing, and deleting users.
 */
class UserPolicy
{
    /**
     * Check if user can view another user
     *
     * @param \Authorization\IdentityInterface|null $identity Current user identity
     * @param \App\Model\Entity\User $resource User being viewed
     * @return bool
     */
    public function canView(?IdentityInterface $identity, User $resource): bool
    {
        // Admins can view any user
        if ($this->isAdmin($identity)) {
            return true;
        }

        // Users can view their own profile
        return $this->isOwner($identity, $resource);
    }

    /**
     * Check if user can edit another user
     *
     * @param \Authorization\IdentityInterface|null $identity Current user identity
     * @param \App\Model\Entity\User $resource User being edited
     * @return bool
     */
    public function canEdit(?IdentityInterface $identity, User $resource): bool
    {
        // Admins can edit any user
        if ($this->isAdmin($identity)) {
            return true;
        }

        // Users can edit their own profile (limited fields)
        return $this->isOwner($identity, $resource);
    }

    /**
     * Check if user can delete another user
     *
     * @param \Authorization\IdentityInterface|null $identity Current user identity
     * @param \App\Model\Entity\User $resource User being deleted
     * @return bool
     */
    public function canDelete(?IdentityInterface $identity, User $resource): bool
    {
        // Only admins can delete users
        if (!$this->isAdmin($identity)) {
            return false;
        }

        // Admins cannot delete themselves
        return !$this->isOwner($identity, $resource);
    }

    /**
     * Check if user can create new users
     *
     * @param \Authorization\IdentityInterface|null $identity Current user identity
     * @param \App\Model\Entity\User $resource New user entity
     * @return bool
     */
    public function canAdd(?IdentityInterface $identity, User $resource): bool
    {
        // Only admins can create users via admin panel
        return $this->isAdmin($identity);
    }

    /**
     * Check if user can approve/activate users
     *
     * @param \Authorization\IdentityInterface|null $identity Current user identity
     * @param \App\Model\Entity\User $resource User being approved
     * @return bool
     */
    public function canApprove(?IdentityInterface $identity, User $resource): bool
    {
        // Only admins can approve users
        return $this->isAdmin($identity);
    }

    /**
     * Check if identity is an admin
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @return bool
     */
    protected function isAdmin(?IdentityInterface $identity): bool
    {
        if ($identity === null) {
            return false;
        }

        $user = $identity->getOriginalData();
        $role = $this->extractField($user, 'role');

        return $role === 'admin';
    }

    /**
     * Check if identity owns the resource
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param \App\Model\Entity\User $resource User resource
     * @return bool
     */
    protected function isOwner(?IdentityInterface $identity, User $resource): bool
    {
        if ($identity === null) {
            return false;
        }

        $user = $identity->getOriginalData();
        $userId = $this->extractField($user, 'id');

        return $userId === $resource->id;
    }

    /**
     * Extract field from user data
     *
     * @param mixed $data User data
     * @param string $field Field name
     * @return mixed
     */
    protected function extractField(mixed $data, string $field): mixed
    {
        if (is_array($data)) {
            return $data[$field] ?? null;
        }

        if ($data instanceof ArrayAccess && isset($data[$field])) {
            return $data[$field];
        }

        if (is_object($data) && method_exists($data, 'get')) {
            try {
                return $data->get($field);
            } catch (Throwable $e) {
                // Fall through
            }
        }

        return null;
    }
}
