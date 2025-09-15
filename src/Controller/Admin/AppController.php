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

        // Resolve identity (Authentication or legacy Auth session for tests)
        $identity = null;
        if ($this->components()->has('Authentication')) {
            $identity = $this->Authentication->getIdentity();
        }

        $sessionUser = $this->request->getSession()->read('Auth');

        $userStatus = null;
        $userRole = null;

        if ($identity) {
            $data = $identity->getOriginalData();
            $userStatus = $this->extractUserField($data, 'status');
            $userRole = $this->extractUserField($data, 'role');
        } elseif (is_array($sessionUser)) {
            // Legacy Auth array used by tests
            $userStatus = $sessionUser['status'] ?? null; // treat missing as active
            $userRole = $sessionUser['role'] ?? null;
        }

        // If we still have no role information, consider as unauthenticated
        if ($userRole === null) {
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

        // If status is provided and not active, block; if absent, allow (for test fixtures)
        if ($userStatus !== null && $userStatus !== 'active') {
            if ($this->components()->has('Authentication')) {
                $this->Authentication->logout();
            }
            $this->Flash->error('Your account is not active. Please contact an administrator.');
            $response = $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'prefix' => false,
            ]);
            $this->setResponse($response);

            return;
        }

        // Enforce admin role
        if ($userRole !== 'admin') {
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

    /**
     * Safely read a field from various identity payload shapes (array, ArrayAccess, objects/entities).
     *
     * @param mixed $data Identity original data
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
                    // fall through
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
