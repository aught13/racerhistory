<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<int,\App\Model\Entity\Role> $roles
 */

$this->assign('title', 'Roles');
?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-2">Roles</h1>
            <p class="text-muted mb-0">Manage database-backed RBAC roles and their per-module permission matrix.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Role</th>
                        <th>Permission Rows</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role) : ?>
                        <tr>
                            <td>
                                <strong><?= h($role->name) ?></strong>
                            </td>
                            <td>
                                <?= count((array)$role->permissions) ?>
                            </td>
                            <td class="text-end">
                                <?= $this->Html->link('Edit Matrix', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'edit', $role->id], ['class' => 'btn btn-primary btn-sm', 'data-turbo-frame' => 'admin-content']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
