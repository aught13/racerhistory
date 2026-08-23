<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\RolesAdminService;
use Cake\Http\Response;

class RolesController extends AppController
{
    private RolesAdminService $rolesAdminService;

    /**
     * Initialize the roles admin controller.
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->rolesAdminService = new RolesAdminService();
    }

    /**
     * Display the list of RBAC roles.
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);
        $this->set($this->rolesAdminService->getIndexViewData());
    }

    /**
     * Edit the matrix for one RBAC role.
     *
     * @param int $id RBAC role id.
     * @return \Cake\Http\Response|null
     */
    public function edit(int $id): ?Response
    {
        $this->request->allowMethod(['get', 'post', 'put', 'patch']);

        if ($this->request->is(['post', 'put', 'patch'])) {
            $saved = $this->rolesAdminService->savePermissions($id, (array)$this->request->getData('permissions', []));
            if ($saved) {
                $this->Flash->success('Role permissions have been updated.');
            } else {
                $this->Flash->error('Role permissions could not be updated. Please review the matrix and try again.');
            }

            return $this->redirect(['action' => 'edit', $id]);
        }

        $this->set($this->rolesAdminService->getEditViewData($id));

        return null;
    }
}
