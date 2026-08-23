<?php
declare(strict_types=1);

namespace App\Policy;

use App\Service\RbacPermissionService;
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
    private RbacPermissionService $rbacPermissionService;

    /**
     * @param \App\Service\RbacPermissionService|null $rbacPermissionService Optional RBAC service override.
     */
    public function __construct(?RbacPermissionService $rbacPermissionService = null)
    {
        $this->rbacPermissionService = $rbacPermissionService ?? new RbacPermissionService();
    }

    /**
     * Check if user can access a given request.
     *
     * CakeDC/Users uses this via AuthorizationService::can($request, 'access')
     * to validate redirect URLs after login.
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param \Cake\Http\ServerRequest $request Request object
     * @return bool
     */
    public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool
    {
        $path = $request->getUri()->getPath();

        // Never allow redirecting into DebugKit.
        if (str_starts_with($path, '/debug-kit')) {
            return false;
        }

        $isAdminRequest =
            ($request->getParam('prefix') === 'Admin')
            || str_starts_with($path, '/admin');

        if ($isAdminRequest) {
            return $this->canAccessAdmin($identity, $request);
        }

        // Public pages are accessible.
        return true;
    }

    /**
     * Check if user can access admin area
     *
     * @param \Authorization\IdentityInterface|null $identity User identity
     * @param \Cake\Http\ServerRequest $request Request object
     * @return bool
     */
    public function canAccessAdmin(?IdentityInterface $identity, ServerRequest $request): bool
    {
        return $this->rbacPermissionService->canAccessAdminRequest(
            $identity,
            (string)$request->getParam('controller'),
            (string)$request->getParam('action'),
            $request->getUri()->getPath(),
        );
    }
}
