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
                ['class' => 'btn btn-warning mb-3', 'style' => 'display:inline;']
            ) ?>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <?php if ($hasInactive): ?>
            <h2 class="mb-3">Pending Users</h2>
            <form id="bulk-action-form" method="post">
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
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><input type="checkbox" name="user_ids[]" value="<?= $user->id ?>" class="user-checkbox">
                            </td>
                            <td><?= h($user->username) ?></td>
                            <td><?= h($user->email) ?></td>
                            <td><?= h($user->role) ?></td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'approve', $user->id]) ?>"
                                    class="btn btn-sm btn-success">Approve</a>
                                <?= $this->Form->postLink('Delete',
                                    ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'delete', $user->id],
                                    ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Are you sure you want to delete this user?']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?php else: ?>
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
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Use $allUsers as a view variable (array or collection)
                    if (isset($allUsers) && is_iterable($allUsers) && count($allUsers)):
                        foreach ($allUsers as $user): ?>
                    <tr>
                        <td><?= h($user->username) ?></td>
                        <td><?= h($user->email) ?></td>
                        <td>
                            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'manage', $user->id]) ?>"
                                class="btn btn-sm btn-primary">Manage</a>
                        </td>
                    </tr>
                    <?php endforeach;
                    else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No users found.</td>
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
        } else if (action === 'delete') {
            form.attr('action',
                "<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'bulkDelete']) ?>"
            );
        }
        this.submit();
    });
});
</script>
