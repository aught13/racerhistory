<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;

/**
 * Admin Application Controller
 *
 * All admin controllers should extend this class.
 * This controller enforces admin authentication and role checking.
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Cake\Controller\Component\FormProtectionComponent $FormProtection
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
    }

    /**
     * Before filter callback.
     *
     * Enforces admin authentication and role checking for all admin routes.
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Allow the login action to render without auth enforcement
        $action = $this->request->getParam('action');
        if ($action === 'login') {
            return; // Skip checks so login form renders (tests expect 200 OK)
        }

        // Check if user is authenticated
        $identity = $this->Authentication->getIdentity();

        if (!$identity) {
            // Not authenticated - redirect to login
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

        // Check if user is active
        /** @var \App\Model\Entity\User $user */
        $user = $identity->getOriginalData();
        if ($user->status !== 'active') {
            $this->Authentication->logout();
            $this->Flash->error('Your account is not active. Please contact an administrator.');
            $response = $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
            ]);
            $this->setResponse($response);

            return;
        }

        // Check if user has admin role
        if ($user->role !== 'admin') {
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
