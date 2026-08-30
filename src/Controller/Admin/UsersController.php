<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\User;
use App\Service\ImageBrowseService;
use App\Service\ImageStorageService;
use App\Service\RbacPermissionService;
use App\Service\RolesAdminService;
use App\Service\SiteOptionsService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\ORM\Query\SelectQuery;
use Throwable;

/**
 * Admin Users Controller
 *
 * Handles administrative user management operations.
 * Provides functionality for user administration, approval, and bulk operations.
 *
 * @property \App\Controller\Component\UserManagerComponent $UserManager
 * @property \App\Model\Table\UsersTable $Users
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class UsersController extends AppController
{
    private SiteOptionsService $siteOptionsService;

    private RolesAdminService $rolesAdminService;

    private RbacPermissionService $rbacPermissionService;

    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->siteOptionsService = new SiteOptionsService();
        $this->rolesAdminService = new RolesAdminService();
        $this->rbacPermissionService = new RbacPermissionService();

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
        $identity = $this->request->getAttribute('identity');

        // Get inactive users (pending approval)
        $users = $this->scopeUsersQuery($identity, 'read')
            ->where(['Users.status !=' => 'active'])
            ->all();
        $hasInactive = !$users->isEmpty();

        // Get all users for search table
        $allUsers = $this->scopeUsersQuery($identity, 'read')
            ->orderBy(['Users.username' => 'ASC'])
            ->all();

        $registrationEnabled = (bool)$this->siteOptionsService->getRuntimeSetting('registration', true);

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
            $response = $this->UserManager->createUser($this, (array)$this->request->getData());
            if ($response !== null) {
                return $response;
            }
        } else {
            $user = $this->Users->newEmptyEntity();
            $this->set(compact('user'));
        }

        $this->setUserFormOptions();

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
        $user = $this->getAuthorizedUserForAbility($id, 'update');

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();
            Log::debug('Admin Users::edit incoming data keys', ['keys' => array_keys((array)$data)]);
            Log::debug('Admin Users::edit social_links raw', ['social_links' => $data['social_links'] ?? null]);

            // Normalize `social_links` to a valid JSON array string before
            // patching. The live schema is JSON-backed (with a JSON validity check),
            // so plain newline strings are not valid persisted values.
            if (array_key_exists('social_links', $data)) {
                $sl = $data['social_links'];
                if ($sl === '' || $sl === null) {
                    $data['social_links'] = null;
                } elseif (is_string($sl)) {
                    $decoded = json_decode((string)$sl, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $arr = array_values(array_filter(
                            array_map('trim', $decoded),
                            static fn($v) => trim((string)$v) !== '',
                        ));
                        $data['social_links'] = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    } else {
                        $parts = preg_split("/\r\n|\n|\r/", (string)$sl);
                        $arr = array_values(array_filter(array_map('trim', $parts), static fn($v) => $v !== ''));
                        $data['social_links'] = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    }
                } elseif (is_array($sl)) {
                    $arr = array_values(array_filter(array_map('trim', $sl), static fn($v) => trim((string)$v) !== ''));
                    $data['social_links'] = $arr === [] ? null :
                    json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                } else {
                    $data['social_links'] = null;
                }
            }
            // Administrators can manage roles, update display attributes, etc.
            $accessibleFields = [
                'username', 'email', 'first_name', 'last_name', 'role', 'role_id', 'status', 'active',
                'display_name', 'bio', 'website_url', 'social_links', 'profile_image_id',
            ];

            $user = $this->Users->patchEntity($user, $data, [
                'fields' => $accessibleFields,
            ]);

            // Ensure we persist only valid JSON-array data for this field.
            if ($user->isDirty('social_links')) {
                /** @var mixed $slVal */
                $slVal = $user->social_links;
                if ($slVal === '' || $slVal === null) {
                    $user->social_links = null;
                } elseif (is_array($slVal)) {
                    $arr = array_values(array_filter(
                        array_map('trim', $slVal),
                        static fn($v) => trim((string)$v) !== '',
                    ));
                    $user->social_links = $arr === [] ? null :
                    json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                } elseif (is_string($slVal)) {
                    $decoded = json_decode($slVal, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $arr = array_values(array_filter(
                            array_map('trim', $decoded),
                            static fn($v) => trim((string)$v) !== '',
                        ));
                        $user->social_links = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    } else {
                        $parts = preg_split("/\r\n|\n|\r/", $slVal);
                        $arr = array_values(array_filter(array_map('trim', $parts), static fn($v) => $v !== ''));
                        $user->social_links = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    }
                }
            }

            // Handle Avatar / profile image upload
            $file = $this->request->getData('avatar');
            if ($file && is_object($file) && method_exists($file, 'getError') && $file->getError() === UPLOAD_ERR_OK) {
                $imageStorage = new ImageStorageService();
                $ownerId = null;
                if ($this->components()->has('Authentication')) {
                    try {
                        $identity = $this->Authentication->getIdentity();
                    } catch (Throwable $e) {
                        $identity = null;
                    }
                    if ($identity !== null && method_exists($identity, 'getIdentifier')) {
                        $ownerId = (int)$identity->getIdentifier();
                    }
                }
                if ($ownerId === null) {
                    $legacy = $this->getRequest()->getSession()->read('Auth');
                    if (is_array($legacy) && !empty($legacy['id'])) {
                        $ownerId = (int)$legacy['id'];
                    }
                }

                $uploadResult = $imageStorage->upload($file, [], [], $ownerId);

                if (!empty($uploadResult['image']['id'])) {
                    $user->profile_image_id = $uploadResult['image']['id'];
                } else {
                    $this->Flash->error($imageStorage->getLastError() ?: 'Failed to process avatar image.');
                }
            }

            if ($this->Users->save($user)) {
                $this->Flash->success('User has been updated successfully.');

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Unable to update user. Please check the form and try again.');
        }

        $this->set(compact('user'));
        $this->setUserFormOptions();

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
        $user = $this->getAuthorizedUserForAbility($id, 'read');
        $this->set(compact('user'));
        $this->setUserFormOptions();
    }

    /**
     * Populate common select-list options used by admin user forms.
     */
    private function setUserFormOptions(): void
    {
        $imagesList = [];
        try {
            $browse = (new ImageBrowseService())->browse(null, 50);
            foreach (($browse['images'] ?? []) as $img) {
                $imagesList[(int)$img['id']] = sprintf('#%d - %s', $img['id'], $img['original_name']);
            }
        } catch (Throwable $e) {
            $imagesList = [];
        }

        try {
            $roleOptions = $this->rolesAdminService->getRoleOptions();
        } catch (Throwable $e) {
            $roleOptions = [];
        }

        $this->set(compact('imagesList', 'roleOptions'));
    }

    /**
     * Approve a user account.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function approve(string $id)
    {
        $this->getAuthorizedUserForAbility($id, 'update');

        return $this->UserManager->approveUser($this, $id);
    }

    /**
     * Toggle manual approval flag safely.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function toggleApproval(string $id)
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $user = $this->getAuthorizedUserForAbility($id, 'update');
        $user->active = !$user->active;

        if ($this->Users->save($user)) {
            $this->Flash->success('User approval toggled.');
        } else {
            $this->Flash->error('Unable to toggle approval.');
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Delete a user account.
     *
     * @param string $id User ID
     * @return \Cake\Http\Response
     */
    public function delete(string $id)
    {
        $this->getAuthorizedUserForAbility($id, 'delete');

        return $this->UserManager->deleteUser($this, $id);
    }

    /**
     * Bulk activate multiple users.
     *
     * @return \Cake\Http\Response
     */
    public function bulkActivate()
    {
        $data = (array)$this->request->getData();
        if (isset($data['user_ids'])) {
            $ids = array_values(array_filter((array)$data['user_ids'], function ($v) {
                return $v !== '' && $v !== null && ctype_digit((string)$v);
            }));

            $data['user_ids'] = $this->filterAllowedUserIds(
                $ids,
                $this->request->getAttribute('identity'),
                'update',
            );

            $this->request = $this->request->withParsedBody($data);
        }

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
        $data = (array)$this->request->getData();
        if (isset($data['user_ids'])) {
            $ids = array_values(array_filter((array)$data['user_ids'], function ($v) {
                return $v !== '' && $v !== null && ctype_digit((string)$v);
            }));

            $data['user_ids'] = $this->filterAllowedUserIds(
                $ids,
                $this->request->getAttribute('identity'),
                'delete',
            );

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
        $enabled = $this->siteOptionsService->toggleBooleanSetting('registration', true);

        if ($enabled === null) {
            $this->Flash->error('Registration setting could not be updated.');

            return $this->redirect(['action' => 'index']);
        }

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

    /**
     * Apply RBAC read/update/delete scope to users table queries.
     *
     * @param mixed $identity Current authenticated identity.
     * @param string $ability Ability: read, update, or delete.
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function scopeUsersQuery(mixed $identity, string $ability): SelectQuery
    {
        return $this->rbacPermissionService->scopeQuery(
            $identity,
            'Users',
            $this->Users->find(),
            $ability,
            'id',
        );
    }

    /**
     * Load one user constrained by RBAC scope.
     *
     * @param string $id Target user id.
     * @param string $ability Ability: read, update, or delete.
     * @return \App\Model\Entity\User
     */
    private function getAuthorizedUserForAbility(string $id, string $ability): User
    {
        $userId = (int)$id;
        /** @var \App\Model\Entity\User|null $user */
        $user = $this->scopeUsersQuery($this->request->getAttribute('identity'), $ability)
            ->where(['Users.id' => $userId])
            ->first();

        if (!$user instanceof User) {
            throw new RecordNotFoundException('User not found or not accessible.');
        }

        return $user;
    }

    /**
     * Filter posted ids against the caller's RBAC scope for a users ability.
     *
     * @param array<int|string> $ids Candidate user ids.
     * @param mixed $identity Current authenticated identity.
     * @param string $ability Ability: update or delete.
     * @return array<int,int>
     */
    private function filterAllowedUserIds(array $ids, mixed $identity, string $ability): array
    {
        $normalized = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        if ($normalized === []) {
            return [];
        }

        $query = $this->scopeUsersQuery($identity, $ability)
            ->select(['Users.id'])
            ->where(['Users.id IN' => $normalized])
            ->enableHydration(false);

        $allowed = [];
        foreach ($query->all() as $row) {
            if (is_array($row) && isset($row['id']) && is_numeric($row['id'])) {
                $allowed[] = (int)$row['id'];
            }
        }

        return $allowed;
    }
}
