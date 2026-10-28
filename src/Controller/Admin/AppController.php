<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use Authorization\Exception\ForbiddenException;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;

/**
 * Admin Application Controller
 *
 * All admin controllers should extend this class.
 * This controller enforces admin authentication and authorization.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \Cake\Controller\Component\FormProtectionComponent $FormProtection
 * @property \App\Controller\Component\AdminDataScopeComponent $AdminDataScope
 */
class AppController extends BaseController
{
    /**
     * Initialization hook method.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setLayout('admin');

        // Load Authorization component
        $this->loadComponent('Authorization.Authorization');
        $this->loadComponent('AdminDataScope');
    }

    /**
     * Before filter callback.
     * Enforces admin authentication and authorization for all admin routes.
     *
     * @param \Cake\Event\EventInterface $event
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Skip authorization for login page
        $action = $this->request->getParam('action');
        if ($action === 'login') {
            return;
        }

        // Check if user is authenticated
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error('You must be logged in to access the admin area.');
            $response = $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
                '?' => ['redirect' => $this->request->getRequestTarget()],
            ]);
            $this->setResponse($response);

            return;
        }

        // Use authorization policy to check admin access
        try {
            $this->Authorization->authorize($this->request, 'accessAdmin');
        } catch (ForbiddenException $e) {
            $this->Flash->error('You do not have permission to access the admin area.');
            $response = $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
                '?' => ['redirect' => $this->request->getRequestTarget()],
            ]);
            $this->setResponse($response);

            return;
        }

        $controller = (string)$this->request->getParam('controller');
        if (
            str_starts_with($controller, 'StatBasket')
            && !$this->AdminDataScope->canAccessStatAdminRequest(
                $identity,
                $controller,
                (string)$action,
                (int)($this->request->getParam('pass')[0] ?? 0),
            )
        ) {
            throw new NotFoundException('Stat record not found.');
        }
    }
}
