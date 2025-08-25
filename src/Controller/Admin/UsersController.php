<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Event\EventInterface;
use Cake\Http\Response;

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
        $this->Authentication->addUnauthenticatedActions(['login']);
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

    // Include future associations for delete confirmation counts if needed (placeholder)
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
    public function edit(string $id)
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
    public function manage(string $id)
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
    public function approve(string $id)
    {
        return $this->UserManager->approveUser($this, $id);
    }

    /**
     * Delete a user account.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id)
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
        // Sanitize incoming IDs before delegating
        $data = $this->request->getData();
        if (isset($data['user_ids'])) {
            $data['user_ids'] = array_values(array_filter((array)$data['user_ids'], function ($v) {
                return $v !== '' && $v !== null && ctype_digit((string)$v);
            }));
            $this->request = $this->request->withParsedBody($data);
        }

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
        $newValue = $siteOption && $siteOption->value === 'false' ? 'true' : 'false';
        if ($siteOption) {
            $siteOption->value = $newValue;
            $siteOptionsTable->save($siteOption);
        } else {
            $siteOption = $siteOptionsTable->newEntity([
                'option_key' => 'registration',
                'value' => $newValue,
            ]);
            $siteOptionsTable->save($siteOption);
        }
        $msg = $newValue === 'true' ? 'Registration enabled.' : 'Registration disabled.';
        $this->Flash->success($msg);

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk action dispatcher (activate/delete) for route /admin/users/bulk used in tests.
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $action = $this->request->getData('bulk_action');
        if ($action === 'activate') {
            return $this->bulkActivate();
        }
        if ($action === 'delete') {
            return $this->bulkDelete();
        }
        $this->Flash->error('Invalid bulk action.');

        return $this->redirect(['action' => 'index']);
    }
}
