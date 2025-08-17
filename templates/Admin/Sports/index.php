<?php $this->assign('title', 'Manage Sports'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Sports Management</h1>
            <p class="text-muted mb-3">
                Manage sport categories that teams compete in. Sports are the foundation of the system - each team must
                be assigned to a sport.
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
            <?php if (!$sports->isEmpty()) : ?>
            <form id="bulk-action-form" method="post">
                <div class="mb-2 d-flex align-items-center gap-2" id="sports-bulk-action-bar">
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
                        <?php foreach ($sports as $sport) : ?>
                        <tr>
                            <td><input type="checkbox" name="sport_ids[]" value="<?= $sport->id ?>"
                                    class="sport-checkbox">
                            </td>
                            <td><?= h($sport->sport_name) ?></td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'view', $sport->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <?php
                                    $teamCount = isset($sport->teams) ? count($sport->teams) : 0;
                                    $associated = json_encode([
                                        ['label' => 'Teams', 'count' => $teamCount],
                                    ]);
                                ?>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete-modal"
                                    data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id]) ?>"
                                    data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'edit', $sport->id]) ?>"
                                    data-item-type="sport" data-associated='<?= $associated ?>'
                                    data-form-id="<?= 'delete-form-sport-' . $sport->id ?>" aria-label="Delete sport <?= h($sport->sport_name) ?>">Delete</button>
                                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'delete', $sport->id], 'id' => 'delete-form-sport-' . $sport->id, 'style' => 'display:none']) ?>
                                <?= $this->Form->end() ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'bulkDelete'], 'id' => 'delete-form-sports-bulk', 'style' => 'display:none']) ?>
                <?php
                // Unlock dynamic fields for FormProtection
                $this->Form->unlockField('sport_ids');
                $this->Form->unlockField('bulk_action');
                ?>
                <?= $this->Form->hidden('sport_ids[]', ['value' => '']) ?>
                <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
                <?= $this->Form->end() ?>
            <?php else : ?>
            <div class=" alert alert-info">No sports have been created yet.
        </div>
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

    // Handle bulk action form submission -> open modal with selected item names
    $('#bulk-action-form').on('submit', function(e) {
        e.preventDefault();
        var action = $('#bulk-action-select').val();
        if (!action) return;
        if (action === 'delete') {
            var names = $('.sport-checkbox:checked').map(function() {
                return $(this).closest('tr').find('td:nth-child(2)').text().trim();
            }).get();
            var ids = $('.sport-checkbox:checked').map(function() { return $(this).val(); }).get();
                        window.showConfirmDelete && window.showConfirmDelete({
                            deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sports', 'action' => 'bulkDelete']) ?>',
                            itemType: 'sports (bulk)',
                            associated: JSON.stringify(names),
                            ids: JSON.stringify(ids),
                            idsName: 'sport_ids[]',
                            formId: 'delete-form-sports-bulk',
                            bulkAction: 'delete'
                        });
        }
    });
});
</script>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'sport']) ?>
