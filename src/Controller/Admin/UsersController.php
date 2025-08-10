<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Event\EventInterface;

/**
 * Admin Users Controller
 *
 * Handles administrative user management operations.
 * Provides functionality for user administration, approval, and bulk operations.
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

        // Load UserManager component for admin-specific logic
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

        // Allow login action without authentication
        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * Admin login action.
     *
     * @return \Cake\Http\Response|null
     */
    public function login()
    {
        return $this->UserManager->login($this);
    }

    /**
     * List all users for administration.
     *
     * @return void
     */
    public function index()
    {
        $users = $this->Users->find()->where(['status !=' => 'active'])->all();
        $hasInactive = !$users->isEmpty();
        $allUsers = $this->Users->find()->all();

        // Fetch registration option
        $siteOptionsTable = $this->fetchTable('SiteOptions');
        $siteOption = $siteOptionsTable->find()->where(['option_key' => 'registration'])->first();
        $registrationEnabled = !$siteOption || $siteOption->value === 'true';

        $this->set(compact('users', 'hasInactive', 'allUsers', 'registrationEnabled'));
    }

    /**
     * Add new user form and processing.
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        if ($this->request->is('post')) {
            return $this->UserManager->createUser($this, $this->request->getData());
        }
        $user = $this->Users->newEmptyEntity();
        $this->set(compact('user'));

        return null;
    }

    /**
     * Edit user form and processing.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response|null
     */
    public function edit($id)
    {
        if ($this->request->is(['post', 'put', 'patch'])) {
            return $this->UserManager->updateUser($this, $id, $this->request->getData());
        }
        $user = $this->Users->get($id);
        $this->set(compact('user'));

        return null;
    }

    /**
     * Manage user details view.
     *
     * @param string $id User ID
     * @return void
     */
    public function manage($id)
    {
        $user = $this->Users->get($id);
        $this->set(compact('user'));
    }

    /**
     * Approve a user account.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function approve($id)
    {
        return $this->UserManager->approveUser($this, $id);
    }

    /**
     * Delete a user account.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function delete($id)
    {
        return $this->UserManager->deleteUser($this, $id);
    }

    /**
     * Bulk activate multiple users.
     *
     * @return \Cake\Http\Response
     */
    public function bulkActivate()
    {
        return $this->UserManager->bulkActivate($this);
    }

    /**
     * Bulk delete multiple users.
     *
     * @return \Cake\Http\Response
     */
    public function bulkDelete()
    {
        return $this->UserManager->bulkDelete($this);
    }

    /**
     * Toggle registration setting.
     *
     * @return \Cake\Http\Response
     */
    public function toggleRegistration()
    {
        $siteOptionsTable = $this->fetchTable('SiteOptions');
        $siteOption = $siteOptionsTable->find()->where(['option_key' => 'registration'])->first();
        $newValue = ($siteOption && $siteOption->value === 'false') ? 'true' : 'false';
        if ($siteOption) {
            $siteOption->value = $newValue;
            $siteOptionsTable->save($siteOption);
        } else {
            $siteOption = $siteOptionsTable->newEntity([
                'option_key' => 'registration',
                'value' => $newValue
            ]);
            $siteOptionsTable->save($siteOption);
        }
        $msg = $newValue === 'true' ? 'Registration enabled.' : 'Registration disabled.';
        $this->Flash->success($msg);
        return $this->redirect(['action' => 'index']);
    }
}
