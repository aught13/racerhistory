<?php
/**
 * Admin Persons Index
 *
 * Server-side DataTables with Scroller — all data is loaded via AJAX.
 *
 * @var \App\View\AppView $this
 * @var int $personCount
 */
$this->assign('title', 'Manage Persons');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'datatables']);
$bulkDeleteUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'bulkDelete']);
$canCreatePersons = $this->Rbac->can('Persons', 'create');
$canDeletePersons = $this->Rbac->can('Persons', 'delete');
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Persons Management</h1>
            <p class="text-muted mb-3">Manage people records (athletes, coaches, etc.). <?= $personCount ?> total.</p>
            <?php if ($canCreatePersons) : ?>
                <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'add']) ?>"
                   class="btn btn-success mb-3">
                    <i class="bi bi-plus-circle"></i> Add New Person
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col" data-controller="persons-index" data-persons-index-bulk-delete-url-value="<?= h($bulkDeleteUrl) ?>">
            <div class="mb-2 d-flex align-items-center gap-2" id="persons-bulk-action-bar" style="display: none !important;" data-persons-index-target="bulkBar">
                <label for="bulk-action-select-persons" class="form-label mb-0">With Selected:</label>
                <select id="bulk-action-select-persons" name="action" class="form-select form-select-sm w-auto" data-persons-index-target="actionSelect">
                    <option value="">Choose...</option>
                    <?php if ($canDeletePersons) : ?>
                        <option value="delete">Delete</option>
                    <?php endif; ?>
                </select>
                <button type="button" class="btn btn-primary btn-sm" id="bulk-action-btn-persons" disabled data-persons-index-target="bulkButton"<?= $canDeletePersons ? '' : ' aria-disabled="true"' ?>>Go</button>
            </div>

            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="persons-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="persons-search" class="form-control form-control-sm" placeholder="Name…" autocomplete="off" data-persons-index-target="searchInput">
            </div>

            <table id="persons-table"
                   class="table table-striped table-bordered table-hover align-middle w-100"
                   data-datatables-url="<?= h($datatableUrl) ?>"
                   data-persons-index-target="table">
                <thead class="table-dark">
                    <tr>
                        <th class="col-check" style="width:2rem;"><input type="checkbox" id="select-all-persons" title="Select all" data-persons-index-target="selectAll"<?= $canDeletePersons ? '' : ' disabled' ?>></th>
                        <th>Display Name</th>
                        <th>First</th>
                        <th>Last</th>
                        <th>Birth</th>
                        <th class="no-sort" style="width:13rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'bulkDelete'], 'id' => 'delete-form-persons-bulk', 'style' => 'display:none', 'data-persons-index-target' => 'bulkForm']) ?>
            <?php $this->Form->unlockField('person_ids');
            $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('person_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>
