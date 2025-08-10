<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Users Controller
 *
 * Handles user authentication, registration, and account management.
 * This controller manages public user actions like login, logout, and registration.
 *
 * @property \App\Controller\Component\UserManagerComponent $UserManager
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
        $this->loadComponent('UserManager');
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
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'resetPassword']);
    }

    /**
     * User login action.
     *
     * @return \Cake\Http\Response|null Redirects on successful login.
     */
    public function login()
    {
        return $this->UserManager->login($this);
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
        // Check registration setting
        $siteOptionsTable = $this->fetchTable('SiteOptions');
        $siteOption = $siteOptionsTable->find()->where(['option_key' => 'registration'])->first();
        $registrationEnabled = !$siteOption || $siteOption->value === 'true';
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

                return $this->redirect(['action' => 'login']); // Tests expect redirect
            }
            $this->Flash->error('Unable to register user');
        }
        $this->set('user', $user);
    // Return null to render view with flash messages; registration disabled still returns 200
        return null;
    }

    /**
     * Password reset action.
     *
     * @return \Cake\Http\Response|null
     */
    public function resetPassword()
    {
        return $this->UserManager->resetPassword($this);
    }

    // Redirect all other actions to home
    /**
     * Fallback for undefined actions: redirect to home.
     *
     * @param string $name Method name invoked
     * @param array $arguments Arguments passed
     * @return \Cake\Http\Response
     */
    public function __call(string $name, array $arguments): Response
    {
        return $this->redirect('/');
    }
}
