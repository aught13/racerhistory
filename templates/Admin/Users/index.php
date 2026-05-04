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
<div class="container py-4">
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
            <form id="bulk-action-form" method="post">
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
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="approve">Approve</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled>Go</button>
                </div>
                <table class="table table-striped table-bordered" id="users-table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-users"></th>
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
                            <td><input type="checkbox" name="user_ids[]" value="<?= $user->id ?>" class="user-checkbox">
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
            <table class="table table-striped table-bordered" id="search-users-table">
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        "pagingType": "simple_numbers",
        "drawCallback": function(settings) {
            var api = this.api();
            var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
            if (api.page.info().pages <= 1) {
                pagination.hide();
            } else {
                pagination.show();
            }
        }
    });
    $('#search-users-table').DataTable({
        "pagingType": "simple_numbers",
        "drawCallback": function(settings) {
            var api = this.api();
            var pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
            if (api.page.info().pages <= 1) {
                pagination.hide();
            } else {
                pagination.show();
            }
        }
    });

    // Enable/disable bulk activate button
    $(document).on('change', '.user-checkbox, #select-all-users, #bulk-action-select', function() {
        var checked = $('.user-checkbox:checked').length;
        var action = $('#bulk-action-select').val();
        $('#bulk-action-btn').prop('disabled', checked === 0 || !action);
    });
    // Select all checkboxes
    $('#select-all-users').on('change', function() {
        $('.user-checkbox').prop('checked', this.checked).trigger('change');
    });

    // Handle bulk action form submission
    $('#bulk-action-form').on('submit', function(e) {
        e.preventDefault();
        var action = $('#bulk-action-select').val();
        if (!action) return;
        var form = $(this);
        if (action === 'approve') {
            form.attr('action',
                "<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkActivate']) ?>"
            );
            this.submit();
        } else if (action === 'delete') {
            // collect selected IDs and names
            var checked = $('.user-checkbox:checked');
            var ids = checked.map(function() { return $(this).val(); }).get();
            var names = checked.map(function() { return $(this).closest('tr').find('td:nth-child(2)').text().trim(); }).get();

            // create a temporary trigger to open modal with data-ids and associated names
            var modalTrigger = $('<button/>', {
                type: 'button',
                'data-bs-toggle': 'modal',
                'data-bs-target': '#confirm-delete-modal',
                'data-delete-url': "<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkDelete']) ?>",
                'data-item-type': 'users (bulk)',
                'data-associated': JSON.stringify(names),
                'data-ids': JSON.stringify(ids),
                'data-ids-name': 'user_ids[]',
                'data-bulk-action': 'delete',
                'data-form-id': 'delete-form-users-bulk'
            }).appendTo('body');
            modalTrigger.trigger('click');
            setTimeout(function() { modalTrigger.remove(); }, 1000);
        }
    });
});
</script>
<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'user']) ?>
