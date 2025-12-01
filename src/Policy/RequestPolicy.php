<?php
declare(strict_types=1);

namespace App\Policy;

use Authorization\IdentityInterface;
use Cake\Http\ServerRequest;

/**
 * Request Policy
 *
 * Handles authorization for ServerRequest objects.
 * Used for checking access to admin areas and other request-level permissions.
 */
class RequestPolicy
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

        // Check if user is active (either status='active' or active=true)
        $status = $this->extractUserField($user, 'status');
        $active = $this->extractUserField($user, 'active');

        // Accept if status is 'active' OR active boolean is true
        // (for backward compatibility during transition)
        if ($status === 'active' || $active === true || $active === 1) {
            return true;
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

        if ($data instanceof \ArrayAccess && isset($data[$field])) {
            return $data[$field];
        }

        if (is_object($data)) {
            if (method_exists($data, 'get')) {
                try {
                    return $data->get($field);
                } catch (\Throwable $e) {
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
