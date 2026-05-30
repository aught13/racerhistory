<?php
/**
 * @var \App\View\AppView $this
 * @var mixed $allUsers
 * @var mixed $hasInactive
 * @var mixed $registrationEnabled
 * @var \Cake\Collection\CollectionInterface|array<\App\Model\Entity\User> $users
 */
?>
<?php $this->assign('title', 'Manage Users'); ?>
<div
    class="container py-4"
    data-controller="admin-users-index"
    data-admin-users-index-bulk-activate-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkActivate'])) ?>"
    data-admin-users-index-bulk-delete-url-value="<?= h($this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkDelete'])) ?>"
    data-admin-users-index-delete-form-id-value="delete-form-users-bulk"
>
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">User Management</h1>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'add']) ?>"
                class="btn btn-success mb-3 me-2">Add New User</a>
            <?= $this->Form->postLink(
                $registrationEnabled ? 'Disable Registration' : 'Enable Registration',
                ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'toggleRegistration'],
                ['class' => 'btn btn-warning mb-3', 'style' => 'display:inline;'],
            ) ?>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <?php if ($hasInactive) : ?>
            <h2 class="mb-3">Pending Users</h2>
            <form id="bulk-action-form" method="post" data-admin-users-index-target="bulkForm">
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkDelete'], 'id' => 'delete-form-users-bulk', 'style' => 'display:none']) ?>
                <?php
                    $this->Form->unlockField('user_ids');
                    $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('user_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
                <div class="mb-2 d-flex align-items-center gap-2">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto" data-admin-users-index-target="actionSelect">
                        <option value="">Choose...</option>
                        <option value="approve">Approve</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled data-admin-users-index-target="actionButton">Go</button>
                </div>
                <table class="table table-striped table-bordered" id="users-table" data-admin-users-index-target="pendingTable">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-users" data-admin-users-index-target="selectAll"></th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?= $user->id ?>" class="user-checkbox" data-admin-users-index-role="row-checkbox">
                            </td>
                            <td><?= h($user->username) ?></td>
                            <td><?= h(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) ?: '<em>N/A</em>' ?></td>
                            <td><?= h($user->email) ?></td>
                            <td><?= h($user->role) ?></td>
                            <td>
                                <?php if ($user->active) : ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else : ?>
                                    <span class="badge bg-warning text-dark">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'approve', $user->id]) ?>"
                                    class="btn btn-sm btn-success">Approve</a>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                    data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'delete', $user->id]) ?>"
                                    data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'manage', $user->id]) ?>"
                                    data-item-type="user"
                                    data-associated='<?= json_encode([['label' => $user->username, 'detail' => $user->email, 'id' => $user->id]]) ?>'
                                    data-form-id="delete-form-user-<?= $user->id ?>">
                                    Delete
                                </button>
                                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'delete', $user->id], 'id' => 'delete-form-user-' . $user->id, 'style' => 'display:none']) ?>
                                <?= $this->Form->end() ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?php else : ?>
            <div class="alert alert-info">There are no inactive users to approve.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col">
            <h2 class="mb-3">Search Users</h2>
            <table class="table table-striped table-bordered" id="search-users-table" data-admin-users-index-target="searchTable">
                <thead class="table-dark">
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Use $allUsers as a view variable (array or collection)
                    if (isset($allUsers) && is_iterable($allUsers) && count($allUsers)) :
                        foreach ($allUsers as $user) : ?>
                    <tr>
                        <td><?= h($user->username) ?></td>
                        <td><?= h(trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))) ?: '<em>N/A</em>' ?></td>
                        <td><?= h($user->email) ?></td>
                        <td>
                            <?php if (isset($user->status) && $user->status === 'active') : ?>
                                <span class="badge bg-success">Active</span>
                            <?php else : ?>
                                <span class="badge bg-warning text-dark">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'manage', $user->id]) ?>"
                                class="btn btn-sm btn-primary">Manage</a>
                        </td>
                    </tr>
                        <?php endforeach;
                    else : ?>
                    <tr>
                        <td colspan="5" class="text-center">No users found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'user']) ?>
