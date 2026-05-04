<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use Authorization\Exception\ForbiddenException;
use Burzum\CakeServiceLayer\Service\ServiceAwareTrait;
use Cake\Event\EventInterface;

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
 */
class AppController extends BaseController
{
    use ServiceAwareTrait;

    /**
     * Initialization hook method.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->setLayout('admin');

        // Load Authorization component
        $this->loadComponent('Authorization.Authorization');
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
            ]);
            $this->setResponse($response);

            return;
        }
    }
}
