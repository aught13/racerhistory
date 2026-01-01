<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\SiteOptionService;
use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Admin Users Controller
 *
 * Handles administrative user management operations.
 * Provides functionality for user administration, approval, and bulk operations.
 *
 * @property \App\Controller\Component\UserManagerComponent $UserManager
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    private SiteOptionService $siteOptionService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->siteOptionService = new SiteOptionService();

        // Load UserManager component for admin-specific logic
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

        // Allow login action without authentication
        $this->Authentication->addUnauthenticatedActions(['login']);

        // Skip authorization for login action only
        if ($this->request->getParam('action') === 'login') {
            $this->Authorization->skipAuthorization();
        }
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
        // Get inactive users (pending approval)
        $users = $this->Users->find()->where(['status !=' => 'active'])->all();
        $hasInactive = !$users->isEmpty();

        // Get all users for search table
        $allUsers = $this->Users->find()->orderBy(['username' => 'ASC'])->all();

        $registrationEnabled = $this->siteOptionService->getBooleanOption('registration', true);

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
        $enabled = $this->siteOptionService->toggleBooleanOption('registration', true);
        $this->Flash->success($enabled ? 'Registration enabled.' : 'Registration disabled.');

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
