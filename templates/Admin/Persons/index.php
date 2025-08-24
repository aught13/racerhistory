<?php $this->assign('title', 'Manage Persons'); ?>
<div class="container py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-3">Persons Management</h1>
            <p class="text-muted mb-3">Manage people records (athletes, coaches, etc.).</p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'add']) ?>"
                class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Person
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <?php if (!$persons->isEmpty()) : ?>
            <form id="bulk-action-form-persons" method="post">
                <div class="mb-2 d-flex align-items-center gap-2" id="persons-bulk-action-bar">
                    <label for="bulk-action-select-persons" class="form-label mb-0">With Selected:</label>
                    <select id="bulk-action-select-persons" name="action" class="form-select form-select-sm w-auto">
                        <option value="">Choose...</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" id="bulk-action-btn-persons"
                        disabled>Go</button>
                </div>

                <table class="table table-striped table-bordered" id="persons-table">
                    <thead class="table-dark">
                        <tr>
                            <th><input type="checkbox" id="select-all-persons"></th>
                            <th>Display Name</th>
                            <th>First</th>
                            <th>Last</th>
                            <th>Birth</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($persons as $person) : ?>
                        <tr>
                            <td><input type="checkbox" name="person_ids[]" value="<?= $person->id ?>"
                                    class="person-checkbox"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?= $this->element('person_image', ['person' => $person, 'size' => 'small', 'class' => 'me-2']) ?>
                                    <?= h($person->display ?? ($person->first . ' ' . $person->last)) ?>
                                </div>
                            </td>
                            <td><?= h($person->first) ?></td>
                            <td><?= h($person->last) ?></td>
                            <td><?= h($person->birth) ?></td>
                            <td>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'view', $person->id]) ?>"
                                    class="btn btn-sm btn-info">View</a>
                                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>"
                                    class="btn btn-sm btn-primary">Edit</a>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#confirm-delete-modal"
                                    data-delete-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'delete', $person->id]) ?>"
                                    data-edit-url="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'edit', $person->id]) ?>"
                                    data-item-type="person" data-form-id="<?= 'delete-form-person-' . $person->id ?>"
                                    aria-label="Delete person <?= h($person->display) ?>">Delete</button>
                                <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'delete', $person->id], 'id' => 'delete-form-person-' . $person->id, 'style' => 'display:none']) ?>
                                <?= $this->Form->end() ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'bulkDelete'], 'id' => 'delete-form-persons-bulk', 'style' => 'display:none']) ?>
            <?php $this->Form->unlockField('person_ids'); $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('person_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
            <?php else : ?>
            <div class="alert alert-info">No persons have been created yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-3gJwYp8p1H1mJk9g6r5Ge0XEt3G5UpiRaY1o1cnZ6+8=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && $('#persons-table').length) {
            $('#persons-table').DataTable({
                pagingType: 'simple_numbers',
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: 'Search persons:'
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const pagination = $(this).closest('.dataTables_wrapper').find(
                        '.dataTables_paginate');
                    if (api.page.info().pages <= 1) {
                        pagination.hide();
                    } else {
                        pagination.show();
                    }
                }
            });
        }
        const selectAll = document.getElementById('select-all-persons');
        const checkboxes = document.querySelectorAll('.person-checkbox');
        const actionSelect = document.getElementById('bulk-action-select-persons');
        const btn = document.getElementById('bulk-action-btn-persons');

        function update() {
            const checked = document.querySelectorAll('.person-checkbox:checked').length;
            btn.disabled = checked === 0 || !actionSelect.value;
        }
        selectAll && selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            update();
        });
        checkboxes.forEach(cb => cb.addEventListener('change', update));
        actionSelect && actionSelect.addEventListener('change', update);

        document.getElementById('bulk-action-form-persons')?.addEventListener('submit', function(e) {
            e.preventDefault();
            if (actionSelect.value === 'delete') {
                const ids = Array.from(document.querySelectorAll('.person-checkbox:checked')).map(
                    cb => cb.value);
                window.showConfirmDelete && window.showConfirmDelete({
                    deleteUrl: '<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'bulkDelete']) ?>',
                    itemType: 'persons (bulk)',
                    ids: JSON.stringify(ids),
                    idsName: 'person_ids[]',
                    formId: 'delete-form-persons-bulk',
                    bulkAction: 'delete'
                });
            }
        });
    });
})();
</script>
