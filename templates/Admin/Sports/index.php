<?php $this->assign('title', 'Manage Sports'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Sports Management</h1>
            <p class="text-muted mb-3">
                Manage sport categories that teams compete in. Sports are the foundation of the system - each team must be assigned to a sport.
                Sport names must be unique across the system.
            </p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Sport
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="mb-3">All Sports</h2>
            <?php if (!$sports->isEmpty()): ?>
            <form id="bulk-action-form" method="post">
                <div class="mb-2 d-flex align-items-center gap-2">
                    <label for="bulk-action-select" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn" disabled>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="sports-table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-sports"></th>
                            <th>Sport Name <small class="text-light">(Unique, 162 chars max)</small></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sports as $sport): ?>
                        <tr>
                            <td><input type="checkbox" name="sport_ids[]" value="<?= $sport->id ?>" class="sport-checkbox">
                            </td>
                            <td><?= h($sport->sport_name) ?></td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $sport->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <?= $this->Form->postLink('Delete',
                                    ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id],
                                    ['class' => 'btn btn-sm btn-danger', 'confirm' => 'Are you sure you want to delete this sport?']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?php else: ?>
            <div class="alert alert-info">No sports have been created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function() {
    $('#sports-table').DataTable({
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

    // Enable/disable bulk action button
    $(document).on('change', '.sport-checkbox, #select-all-sports, #bulk-action-select', function() {
        var checked = $('.sport-checkbox:checked').length;
        var action = $('#bulk-action-select').val();
        $('#bulk-action-btn').prop('disabled', checked === 0 || !action);
    });

    // Select all checkboxes
    $('#select-all-sports').on('change', function() {
        $('.sport-checkbox').prop('checked', this.checked).trigger('change');
    });

    // Handle bulk action form submission
    $('#bulk-action-form').on('submit', function(e) {
        e.preventDefault();
        var action = $('#bulk-action-select').val();
        if (!action) return;
        var form = $(this);
        if (action === 'delete') {
            if (confirm('Are you sure you want to delete the selected sports?')) {
                form.attr('action',
                    "<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'bulkDelete']) ?>"
                );
                this.submit();
            }
        }
    });
});
</script>
