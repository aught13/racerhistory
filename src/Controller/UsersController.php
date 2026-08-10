<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\PasswordResetService;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Users Controller
 *
 * Handles user authentication, registration, and account management.
 * This controller manages public user actions like login, logout, and registration.
 * It uses the UserManagerComponent for handling user-related operations and the Authentication and Authorization components for managing access control.
 * All actions that are meant to be publicly accessible (login, logout, register, resetPassword) skip authorization checks to allow unauthenticated users to access them.
 * The controller also includes a fallback __call method to redirect any undefined actions to the home page
 * to prevent access to unintended endpoints and improve user experience.
 *
 * Security:
 * - The login and register actions check for the presence of a redirect parameter to prevent open redirect
 * vulnerabilities, ensuring that redirects only occur to internal paths.
 * - The register action checks a site option to determine if registration is enabled before allowing new user
 * creation, providing a mechanism to disable registration if needed.
 * - The resetPassword action is handled by the UserManagerComponent, which should implement appropriate security measures for password resets, such as token-based verification and rate limiting.
 *
 * Dependencies:
 * - UserManagerComponent: Provides methods for handling user login, logout, registration, and password
 * reset operations, abstracting away the details of these processes from the controller and allowing for cleaner code and easier maintenance.
 *
 * Components:
 * - AuthenticationComponent: Used to manage user authentication, including identifying the currently logged-in user and
 * handling login and logout processes. It is configured to allow unauthenticated access to the login, logout, register, and resetPassword actions.
 * - AuthorizationComponent: Used to manage access control, but is configured to skip authorization checks for
 * public actions to allow unauthenticated users to access them. This ensures that users can log in, register, and reset their passwords without needing prior authorization.
 *
 * @property \App\Controller\Component\UserManagerComponent $UserManager
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Model\Table\UsersTable $Users
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class UsersController extends AppController
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
        // FormProtection is configured in AppController; do not load it twice here.
        $this->loadComponent('UserManager');

        // Load Authorization component
        $this->loadComponent('Authorization.Authorization');
    }

    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'resetPassword', 'resetPasswordForm']);

        // Skip authorization for public and self-service actions
        $action = $this->request->getParam('action');
        $publicActions = ['login', 'logout', 'register', 'resetPassword', 'resetPasswordForm', 'changePassword'];
        if (in_array($action, $publicActions, true)) {
            $this->Authorization->skipAuthorization();
        }
    }

    /**
     * User login action.
     *
     * @return \Cake\Http\Response|null Redirects on successful login.
     */
    public function login()
    {
        $response = $this->UserManager->login($this);
        if ($response instanceof Response) {
            return $response;
        }
        // If already authenticated (identity exists) honor redirect param even on GET
        $identity = $this->Authentication->getIdentity();
        if ($identity) {
            $redirect = $this->request->getQuery('redirect') ?: $this->request->getData('redirect');
            if ($redirect && str_starts_with($redirect, '/')) {
                return $this->redirect($redirect);
            }
        }
        // Fallback: if POST and identity resolved but component provided no redirect, honor ?redirect query
        if ($this->request->is('post')) {
            if ($identity) {
                $redirect = $this->request->getQuery('redirect') ?: $this->request->getData('redirect');
                if ($redirect && str_starts_with($redirect, '/')) {
                    return $this->redirect($redirect);
                }
            }
        }

        return null;
    }

    /**
     * User logout action.
     *
     * @return \Cake\Http\Response Redirects to login page.
     */
    public function logout()
    {
        return $this->UserManager->logout($this);
    }

    /**
     * User registration action.
     *
     * Allows new users to register if registration is enabled.
     *
     * @return \Cake\Http\Response|null Redirects on successful registration.
     */
    public function register()
    {
        // Check registration setting from runtime site options.
        $registrationEnabled = (bool)Configure::read('SiteOptions.registration', true);
        if (!$registrationEnabled) {
            $this->Flash->error('Registration is currently disabled.');
        }

        $data = $this->request->is('post') ? $this->request->getData() : [];
        $user = null;
        if ($this->request->is('post') && $registrationEnabled) {
            $data['role'] = 'user';
            $data['status'] = 'active';
            $usersTable = $this->fetchTable('Users');
            $user = $usersTable->newEmptyEntity();
            $user = $usersTable->patchEntity($user, $data);
            if ($usersTable->save($user)) {
                $this->request->getSession()->write('Auth.username', $user->get('username'));
                $this->Flash->success('Registration successful.');

                return $this->redirect(['action' => 'login']); // Tests expect redirect to login
            }
            $this->Flash->error('Unable to register user');
        }
        $this->set('user', $user);

        // Return null to render view with flash messages; registration disabled still returns 200
        return null;
    }

    /**
     * Password reset (step 1): request a reset link by email.
     *
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(): ?Response
    {
        if ($this->request->is('post')) {
            $email = (string)($this->request->getData('email') ?? '');
            $service = new PasswordResetService();
            $service->generateAndSendToken($email);
            // Unified message prevents account enumeration
            $this->Flash->success('If your email exists, a reset link will be sent.');
        }

        return null;
    }

    /**
     * Password reset (step 2): consume a one-time token and set a new password.
     *
     * @param string $token The reset token from the email link.
     * @return \Cake\Http\Response|null
     */
    public function resetPasswordForm(string $token): ?Response
    {
        $service = new PasswordResetService();
        $user = $service->validateToken($token);

        if ($user === null) {
            $this->Flash->error('This password reset link is invalid or has expired. Please request a new one.');

            return $this->redirect(['action' => 'resetPassword']);
        }

        if ($this->request->is('post')) {
            $newPassword = (string)($this->request->getData('password') ?? '');
            $confirmPassword = (string)($this->request->getData('confirm_password') ?? '');

            if ($newPassword === '' || strlen($newPassword) < 8) {
                $this->Flash->error('Password must be at least 8 characters.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->Flash->error('Passwords do not match.');
            } elseif ($service->consumeToken($token, $newPassword)) {
                $this->Flash->success('Your password has been reset. You can now log in.');

                return $this->redirect(['action' => 'login']);
            } else {
                $this->Flash->error('Unable to reset password. The link may have expired. Please try again.');

                return $this->redirect(['action' => 'resetPassword']);
            }
        }

        $this->set(compact('token'));

        return null;
    }

    /**
     * Authenticated self-service password change.
     *
     * @return \Cake\Http\Response|null
     */
    public function changePassword(): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->redirect(['action' => 'login']);
        }

        if ($this->request->is('post')) {
            $currentPassword = (string)($this->request->getData('current_password') ?? '');
            $newPassword = (string)($this->request->getData('password') ?? '');
            $confirmPassword = (string)($this->request->getData('confirm_password') ?? '');

            if ($currentPassword === '') {
                $this->Flash->error('Current password is required.');
            } elseif ($newPassword === '' || strlen($newPassword) < 8) {
                $this->Flash->error('New password must be at least 8 characters.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->Flash->error('New passwords do not match.');
            } else {
                $service = new PasswordResetService();
                $userId = (int)$identity->getIdentifier();
                if ($service->changePassword($userId, $currentPassword, $newPassword)) {
                    $this->Flash->success('Your password has been updated.');

                    return $this->redirect('/');
                }
                $this->Flash->error('Current password is incorrect.');
            }
        }

        return null;
    }

    // Redirect all other actions to home
    /**
     * Fallback for undefined actions: redirect to home.
     *
     * @param string $name      Method name invoked
     * @param array  $arguments Arguments passed
     * @return \Cake\Http\Response
     */
    public function __call(string $name, array $arguments): Response
    {
        return $this->redirect('/');
    }
}
