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
?>

<?php $this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
<?php $this->end(); ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Persons Management</h1>
            <p class="text-muted mb-3">Manage people records (athletes, coaches, etc.). <?= $personCount ?> total.</p>
            <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'add']) ?>"
               class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Person
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="mb-2 d-flex align-items-center gap-2" id="persons-bulk-action-bar" style="display: none !important;">
                <label for="bulk-action-select-persons" class="form-label mb-0">With Selected:</label>
                <select id="bulk-action-select-persons" name="action" class="form-select form-select-sm w-auto">
                    <option value="">Choose...</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="button" class="btn btn-primary btn-sm" id="bulk-action-btn-persons" disabled>Go</button>
            </div>

            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="persons-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="persons-search" class="form-control form-control-sm" placeholder="Name…" autocomplete="off">
            </div>

            <table id="persons-table"
                   class="table table-striped table-bordered table-hover align-middle w-100"
                   data-datatables-url="<?= h($datatableUrl) ?>">
                <thead class="table-dark">
                    <tr>
                        <th class="col-check" style="width:2rem;"><input type="checkbox" id="select-all-persons" title="Select all"></th>
                        <th>Display Name</th>
                        <th>First</th>
                        <th>Last</th>
                        <th>Birth</th>
                        <th class="no-sort" style="width:13rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <?= $this->Form->create(null, ['url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'bulkDelete'], 'id' => 'delete-form-persons-bulk', 'style' => 'display:none']) ?>
            <?php $this->Form->unlockField('person_ids');
            $this->Form->unlockField('bulk_action'); ?>
            <?= $this->Form->hidden('person_ids[]', ['value' => '']) ?>
            <?= $this->Form->hidden('bulk_action', ['value' => '']) ?>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?= $this->element('Admin/confirm_delete', ['modalId' => 'confirm-delete-modal', 'itemType' => 'person']) ?>

<?php $this->start('script'); ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js"></script>
<script>
(function () {
    'use strict';

    var dtInstance = null;
    var searchDebounce = null;

    function destroyTable() {
        if (dtInstance) {
            try { dtInstance.destroy(false); } catch (_) { /* no-op */ }
            dtInstance = null;
        }
    }

    function initPersonsTable() {
        var tableEl = document.getElementById('persons-table');
        if (!tableEl || !window.jQuery || typeof $.fn.DataTable !== 'function') return;

        // If DataTable already exists (e.g. Turbo restore), destroy first
        if ($.fn.DataTable.isDataTable('#persons-table')) {
            destroyTable();
        }

        var dataUrl = tableEl.dataset.datatablesUrl;

        dtInstance = $('#persons-table').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
            },
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return '<input type="checkbox" class="person-checkbox" value="' + data + '">';
                    },
                },
                { data: 'display', name: 'display' },
                { data: 'first',   name: 'first' },
                { data: 'last',    name: 'last', orderSequence: ['asc', 'desc'] },
                { data: 'birth',   name: 'birth', orderable: false, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false },
            ],
            order: [[3, 'asc']],
            pageLength: 50,
            lengthMenu: [25, 50, 100, 250],
            paging: true,
            pagingType: 'simple_numbers',
            // Scroller for virtual scrolling on large datasets
            scrollY: '60vh',
            scrollCollapse: true,
            scroller: true,
            deferRender: true,
            language: {
                processing: '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…',
                search: '',
                searchPlaceholder: 'Search…',
                zeroRecords: 'No matching persons found.',
                info: 'Showing _START_ to _END_ of _TOTAL_ persons',
                infoEmpty: 'No persons found.',
                infoFiltered: '(filtered from _MAX_ total persons)',
            },
            dom: 'rltip',
        });

        // Connect external search input with debounce
        var searchInput = document.getElementById('persons-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    if (dtInstance) dtInstance.search(searchInput.value).draw();
                }, 250);
            });
        }

        // Select-all checkbox
        var selectAll = document.getElementById('select-all-persons');
        var bulkBar   = document.getElementById('persons-bulk-action-bar');
        var actionSel = document.getElementById('bulk-action-select-persons');
        var bulkBtn   = document.getElementById('bulk-action-btn-persons');

        function getChecked() {
            return Array.from(document.querySelectorAll('.person-checkbox:checked'));
        }

        function updateBulkBar() {
            var checked = getChecked();
            if (bulkBar) bulkBar.style.display = checked.length > 0 ? 'flex' : 'none';
            if (bulkBtn) bulkBtn.disabled = checked.length === 0 || !actionSel.value;
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('person-checkbox')) {
                updateBulkBar();
            }
        });

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.person-checkbox').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                updateBulkBar();
            });
        }

        if (actionSel) actionSel.addEventListener('change', updateBulkBar);

        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                if (actionSel.value === 'delete') {
                    var ids = getChecked().map(function (cb) { return cb.value; });
                    window.showConfirmDelete && window.showConfirmDelete({
                        deleteUrl: '<?= h($bulkDeleteUrl) ?>',
                        itemType: 'persons (bulk)',
                        ids: JSON.stringify(ids),
                        idsName: 'person_ids[]',
                        formId: 'delete-form-persons-bulk',
                        bulkAction: 'delete',
                    });
                }
            });
        }
    }

    // Destroy before Turbo replaces/caches the frame
    var adminFrame = document.getElementById('admin-content');
    if (adminFrame && !adminFrame._personsFrameListenerAttached) {
        adminFrame._personsFrameListenerAttached = true;
        adminFrame.addEventListener('turbo:before-frame-render', destroyTable);
    }
    document.addEventListener('turbo:before-cache', destroyTable);

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPersonsTable);
    } else {
        initPersonsTable();
    }
    document.addEventListener('turbo:load', initPersonsTable);
}());
</script>
<?php $this->end(); ?>
