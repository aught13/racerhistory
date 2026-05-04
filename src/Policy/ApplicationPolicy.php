<?php
declare(strict_types=1);

namespace App\Policy;

use ArrayAccess;
use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;
use Throwable;

/**
 * Application Policy
 *
 * Provides authorization checks for application-wide actions.
 * This policy handles role-based access control for admin areas.
 */
class ApplicationPolicy
{
    /**
     * Check if user can access admin area
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param \Cake\Http\ServerRequest $request Request object
     * @return bool
     */
    public function canAccessAdmin(?IdentityInterface $identity, ServerRequest $request): bool
    {
        if ($identity === null) {
            return false;
        }

        // Get user data from identity
        $user = $identity->getOriginalData();

        // Check if user has admin role
        $role = $this->extractUserField($user, 'role');
        if ($role !== 'admin') {
            return false;
        }

        // Check if user is active
        $active = $this->extractUserField($user, 'active');
        if ($active === false || $active === 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can access public areas
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param \Cake\Http\ServerRequest $request Request object
     * @return bool
     */
    public function canAccessPublic(?IdentityInterface $identity, ServerRequest $request): bool
    {
        // Public areas are accessible to everyone
        return true;
    }

    /**
     * Check if user can edit their own profile
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param mixed $resource Resource being accessed (User entity)
     * @return bool
     */
    public function canEditOwnProfile(?IdentityInterface $identity, mixed $resource): bool
    {
        if ($identity === null) {
            return false;
        }

        $user = $identity->getOriginalData();
        $userId = $this->extractUserField($user, 'id');

        // Check if resource is a User entity and if it's the same user
        if (is_object($resource) && method_exists($resource, 'get')) {
            $resourceId = $resource->get('id');

            return $userId === $resourceId;
        }

        return false;
    }

    /**
     * Safely extract field from various user data formats
     *
     * @param mixed $data User data (array, object, entity)
     * @param string $field Field name
     * @return mixed|null
     */
    private function extractUserField(mixed $data, string $field): mixed
    {
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
                } catch (Throwable $e) {
                    // Fall through
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
