<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;

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
    public function login($controller)
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
    public function processLogin($controller)
    {
        $service = $controller->getRequest()->getAttribute('authentication');
        $result = $service->authenticate($controller->getRequest());

        if (!$result->isValid()) {
            // Authentication failed - let the form handle the error display
            return;
        }

        // Get identity
        $user = null;
        if ($controller->components()->has('Authentication')) {
            $user = $controller->Authentication->getIdentity();
        } else {
            $user = $controller->getRequest()->getAttribute('identity');
        }

        if (!$user) {
            $controller->Flash->error('Authentication succeeded but no user identity found.');
            return;
        }

        if ($user->get('status') !== 'active') {
            if ($controller->components()->has('Authentication')) {
                $controller->Authentication->logout();
            } else {
                $controller->getRequest()->getSession()->delete('Auth');
            }
            $controller->Flash->error('Your account is not active. Please contact an administrator.');
            return;
        }

        // Redirect logic
        $redirect = $controller->getRequest()->getQuery('redirect');
        if ($redirect && strpos($redirect, '/') === 0) {
            return $controller->redirect($redirect);
        }

        $params = $controller->getRequest()->getAttribute('params');
        if (isset($params['prefix']) && $params['prefix'] === 'Admin') {
            return $controller->redirect(['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Admin']);
        }

        return $controller->redirect(['controller' => 'Pages', 'action' => 'display', 'home']);
    }

    /**
     * Handle user logout.
     *
     * @param \Cake\Controller\Controller $controller The controller instance
     * @return \Cake\Http\Response Redirect response
     */
    public function logout($controller)
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
    public function createUser($controller, $data)
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
     */
    public function updateUser($controller, $id, $data)
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
     */
    public function approveUser($controller, $id)
    {
        $usersTable = $controller->fetchTable('Users');
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
     */
    public function deleteUser($controller, $id)
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
     */
    public function bulkActivate($controller)
    {
        $userIds = $controller->getRequest()->getData('user_ids');

        if (empty($userIds)) {
            $controller->Flash->error('No users selected.');
            return $controller->redirect(['action' => 'index']);
        }

        $usersTable = $controller->fetchTable('Users');
        $count = $usersTable->updateAll(
            ['status' => 'active'],
            ['id IN' => $userIds]
        );

        $controller->Flash->success("{$count} user(s) have been activated.");
        return $controller->redirect(['action' => 'index']);
    }

    /**
     * Bulk delete users.
     */
    public function bulkDelete($controller)
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
     */
    public function resetPassword($controller)
    {
        if ($controller->getRequest()->is('post')) {
            $data = $controller->getRequest()->getData();

            if (empty($data['email'])) {
                $controller->Flash->error('Please enter your email address.');
                return null;
            }

            $usersTable = $controller->fetchTable('Users');
            $user = $usersTable->find()->where(['email' => $data['email']])->first();

            if (!$user) {
                $controller->Flash->error('No account found with that email address.');
                return null;
            }

            // Generate a reset token (simplified for now - in production you'd want a proper token system)
            $resetToken = bin2hex(random_bytes(32));
            $user->reset_token = $resetToken;
            $user->reset_token_expires = new \DateTime('+1 hour');

            if ($usersTable->save($user)) {
                // In a real application, you'd send an email here
                $controller->Flash->success('Password reset instructions have been sent to your email.');
                return $controller->redirect(['controller' => 'Users', 'action' => 'login']);
            } else {
                $controller->Flash->error('Unable to process password reset request.');
            }
        }

        return null;
    }
}