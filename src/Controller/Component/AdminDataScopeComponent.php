<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\AdminDataScopeService;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

class AdminDataScopeComponent extends Component
{
    /**
     * Create the component with its data scope service.
     *
     * @param \Cake\Controller\ComponentRegistry $registry The component registry.
     * @param \App\Service\AdminDataScopeService $adminDataScopeService The admin data scope service.
     * @param array<string,mixed> $config The component configuration.
     */
    public function __construct(
        ComponentRegistry $registry,
        private AdminDataScopeService $adminDataScopeService,
        array $config = [],
    ) {
        parent::__construct($registry, $config);
    }

    /**
     * Check whether the identity may access a statistics admin request.
     *
     * @param mixed $identity The authenticated identity.
     * @param string $controller The requested controller.
     * @param string $action The requested action.
     * @param int $recordId The requested record ID.
     * @return bool
     */
    public function canAccessStatAdminRequest(
        mixed $identity,
        string $controller,
        string $action,
        int $recordId,
    ): bool {
        return $this->adminDataScopeService->canAccessStatAdminRequest(
            $identity,
            $controller,
            $action,
            $recordId,
        );
    }
}
