<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use DateTime;

/**
 * UserManagerComponent
 *
 * Handles common user management operations across controllers.
 * This component provides methods for user authentication, registration,
 * password management, and administrative user operations.
 */
class UserManagerComponent extends Component
{
    /**
     * Handle user login - main entry point for controllers.
     *
     * @param \Cake\Controller\Controller $controller The controller instance
     * @return \Cake\Http\Response|null Redirect response or null
     */
    public function login(Controller $controller)
    {
        // Check if this is a POST request (form submission)
        if ($controller->getRequest()->is('post')) {
            return $this->processLogin($controller);
        }

        // GET request - just display the login form
        return null;
    }

    /**
     * Handle user login processing.
     *
     * @param \Cake\Controller\Controller $controller The controller instance
     * @return \Cake\Http\Response|null Redirect response or null
     */
    public function processLogin(Controller $controller)
    {
        $result = $this->obtainAuthResult($controller);
        if (!$this->validateAuthResult($controller, $result)) {
            return null;
        }

        $user = $this->resolveIdentity($controller);
        if (!$user) {
            return null; // Flash + log handled inside helper
        }

        if (!$this->ensureUserIsActive($controller, $user)) {
            return null;
        }

        return $this->determineLoginRedirect($controller);
    }

    /**
     * Obtain authentication result either from component or request attribute.
     *
     * @param \Cake\Controller\Controller $controller Controller
     * @return mixed Authentication result object (plugin specific) or null
     */
    protected function obtainAuthResult(Controller $controller)
    {
        if ($controller->components()->has('Authentication')) {
            return $controller->Authentication->getResult();
        }

        $service = $controller->getRequest()->getAttribute('authentication');

        return $service ? $service->authenticate($controller->getRequest()) : null;
    }

    /**
     * Validate authentication result and set flashes/logging when invalid.
     *
     * @param \Cake\Controller\Controller $controller Controller
     * @param mixed $result Auth result
     * @return bool True when valid
     */
    protected function validateAuthResult(Controller $controller, mixed $result): bool
    {
        if ($result && method_exists($result, 'isValid') && $result->isValid()) {
            return true;
        }

        if (Configure::read('debug')) {
            $errors = $result && method_exists($result, 'getErrors') ? $result->getErrors() : [];
            Log::debug('[Login] Authentication failed', [
                'data' => $controller->getRequest()->getData(),
                'errors' => $errors,
                'status' => $result && method_exists($result, 'getStatus') ? $result->getStatus() : null,
            ]);
        }
        $controller->Flash->error('Invalid username or password');

        return false;
    }

    /**
     * Resolve the authenticated user identity.
     *
     * @param \Cake\Controller\Controller $controller Controller
     * @return mixed Identity or null
     */
    protected function resolveIdentity(Controller $controller): mixed
    {
        $user = $controller->components()->has('Authentication')
            ? $controller->Authentication->getIdentity()
            : $controller->getRequest()->getAttribute('identity');

        if ($user) {
            return $user;
        }

        if (Configure::read('debug')) {
            Log::warning('[Login] Valid auth result but no identity attribute present');
        }
        $controller->Flash->error('Authentication succeeded but no user identity found.');

        return null;
    }

    /**
     * Ensure user has an active status.
     *
     * @param \Cake\Controller\Controller $controller Controller
     * @param mixed $user User identity (array/entity)
     * @return bool True if active
     */
    protected function ensureUserIsActive(Controller $controller, mixed $user): bool
    {
        $status = is_object($user) && method_exists($user, 'get') ? $user->get('status') : ($user['status'] ?? null);
        $id = is_object($user) && method_exists($user, 'get') ? $user->get('id') : ($user['id'] ?? null);

        if ($status === 'active') {
            return true;
        }

        if (Configure::read('debug')) {
            Log::info('[Login] User blocked due to inactive status', [
                'user_id' => $id,
                'status' => $status,
            ]);
        }

        if ($controller->components()->has('Authentication')) {
            $controller->Authentication->logout();
        } else {
            $controller->getRequest()->getSession()->delete('Auth');
        }
        $controller->Flash->error('Your account is not active. Please contact an administrator.');

        return false;
    }

    /**
     * Determine login redirect destination.
     *
     * @param \Cake\Controller\Controller $controller Controller
     * @return \Cake\Http\Response
     */
    protected function determineLoginRedirect(Controller $controller): Response
    {
        $redirect = $controller->getRequest()->getQuery('redirect');
        if ($redirect && strpos((string)$redirect, '/') === 0) {
            if (Configure::read('debug')) {
                Log::debug('[Login] Redirecting to user-supplied redirect parameter', ['redirect' => $redirect]);
            }

            return $controller->redirect($redirect);
        }

        $params = $controller->getRequest()->getAttribute('params');
        if (isset($params['prefix']) && $params['prefix'] === 'Admin') {
            if (Configure::read('debug')) {
                Log::debug('[Login] Redirecting admin user to dashboard');
            }

            return $controller->redirect(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']);
        }

        if (Configure::read('debug')) {
            Log::debug('[Login] Redirecting standard user to home');
        }

        return $controller->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
    }

    /**
     * Handle user logout.
     *
     * @param \Cake\Controller\Controller $controller The controller instance
     * @return \Cake\Http\Response Redirect response
     */
    public function logout(Controller $controller)
    {
        if ($controller->components()->has('Authentication')) {
            $controller->Authentication->logout();
        } else {
            $controller->getRequest()->getSession()->delete('Auth');
        }

        $params = $controller->getRequest()->getAttribute('params');
        if (isset($params['prefix']) && $params['prefix'] === 'Admin') {
            return $controller->redirect(['controller' => 'Users', 'action' => 'login', 'prefix' => 'Admin']);
        }

        return $controller->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Create a new user.
     *
     * @param \Cake\Controller\Controller $controller The controller instance
     * @param array $data User data
     * @return \Cake\Http\Response|null Redirect response or null
     */
    public function createUser(Controller $controller, array $data)
    {
        $usersTable = $controller->fetchTable('Users');
        $user = $usersTable->newEmptyEntity();
        $user = $usersTable->patchEntity($user, $data);

        if ($usersTable->save($user)) {
            $controller->Flash->success('User has been created successfully.');

            return $controller->redirect(['action' => 'index']);
        }

        $controller->Flash->error('Unable to create user. Please check the form and try again.');
        $controller->set(compact('user'));
        // Return a response even on failure for consistency
        return $controller->redirect(['action' => 'add']);
    }

    /**
     * Update an existing user.
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @param string|int $id User id
     * @param array $data Data to patch
     * @return \Cake\Http\Response Redirect response
     */
    public function updateUser(Controller $controller, string|int $id, array $data): Response
    {
        $usersTable = $controller->fetchTable('Users');
        $user = $usersTable->get($id);
        $user = $usersTable->patchEntity($user, $data);

        if ($usersTable->save($user)) {
            $controller->Flash->success('User has been updated successfully.');

            return $controller->redirect(['action' => 'index']);
        }

        $controller->Flash->error('Unable to update user. Please check the form and try again.');
        $controller->set(compact('user'));
        // Return a response even on failure for consistency
        return $controller->redirect(['action' => 'edit', $id]);
    }

    /**
     * Approve a user (set status to active).
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @param string|int $id User id
     * @return \Cake\Http\Response Redirect response
     */
    public function approveUser(Controller $controller, string|int $id): Response
    {
        $usersTable = $controller->fetchTable('Users');
        /** @var \App\Model\Entity\User $user */
        $user = $usersTable->get($id);
        $user->status = 'active';

        if ($usersTable->save($user)) {
            $controller->Flash->success('User has been approved successfully.');
        } else {
            $controller->Flash->error('Unable to approve user.');
        }

        return $controller->redirect(['action' => 'index']);
    }

    /**
     * Delete a user.
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @param string|int $id User id
     * @return \Cake\Http\Response Redirect response
     */
    public function deleteUser(Controller $controller, string|int $id): Response
    {
        $usersTable = $controller->fetchTable('Users');
        $user = $usersTable->get($id);

        if ($usersTable->delete($user)) {
            $controller->Flash->success('User has been deleted successfully.');
        } else {
            $controller->Flash->error('Unable to delete user.');
        }

        return $controller->redirect(['action' => 'index']);
    }

    /**
     * Bulk activate users.
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @return \Cake\Http\Response Redirect response
     */
    public function bulkActivate(Controller $controller): Response
    {
        $userIds = $controller->getRequest()->getData('user_ids');

        if (empty($userIds)) {
            $controller->Flash->error('No users selected.');

            return $controller->redirect(['action' => 'index']);
        }

        $usersTable = $controller->fetchTable('Users');
        $count = $usersTable->updateAll(
            ['status' => 'active'],
            ['id IN' => $userIds],
        );

        $controller->Flash->success("{$count} user(s) have been activated.");

        return $controller->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete users.
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @return \Cake\Http\Response Redirect response
     */
    public function bulkDelete(Controller $controller): Response
    {
        $userIds = $controller->getRequest()->getData('user_ids');

        if (empty($userIds)) {
            $controller->Flash->error('No users selected.');

            return $controller->redirect(['action' => 'index']);
        }

        $usersTable = $controller->fetchTable('Users');
        $count = $usersTable->deleteAll(['id IN' => $userIds]);

        $controller->Flash->success("{$count} user(s) have been deleted.");

        return $controller->redirect(['action' => 'index']);
    }

    /**
     * Handle password reset functionality.
     *
     * @param \Cake\Controller\Controller $controller Controller instance
     * @return \Cake\Http\Response|null Null for render, response for redirects
     */
    public function resetPassword(Controller $controller): ?Response
    {
        if ($controller->getRequest()->is('post')) {
            $data = $controller->getRequest()->getData();
            $email = $data['email'] ?? '';

            if ($email !== '') {
                $usersTable = $controller->fetchTable('Users');
                $user = $usersTable->find()->where(['email' => $email])->first();
                if ($user) {
                    // Generate a reset token (placeholder implementation)
                    $user->reset_token = bin2hex(random_bytes(16));
                    $user->reset_token_expires = new DateTime('+1 hour');
                    $usersTable->save($user); // Ignore save failures silently for unified response
                }
            }

            // Always respond with the same message to avoid account enumeration
            $controller->Flash->success('If your email exists, a reset link will be sent.');

            return null; // Keep same page (tests expect 200 OK, no redirect)
        }

        return null;
    }
}
