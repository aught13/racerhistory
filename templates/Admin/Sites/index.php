<?php
/**
 * @var \App\View\AppView $this
 * @var int $siteCount
 */

$this->assign('title', 'Sites');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Sites', 'action' => 'datatables']);
?>
<?php $this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
<?php $this->end(); ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Sites</h1>
            <p class="text-muted mb-3">Manage site records and their places. <?= (int)$siteCount ?> total.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Site
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="sites-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="sites-search" class="form-control form-control-sm" placeholder="Name or place..." autocomplete="off">
            </div>

            <table
                id="sites-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
            >
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Place</th>
                        <th>Capacity</th>
                        <th class="no-sort" style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

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
            try {
                dtInstance.destroy(false);
            } catch (_) {
                // no-op
            }
            dtInstance = null;
        }
    }

    function initSitesTable() {
        var tableEl = document.getElementById('sites-table');
        if (!tableEl || !window.jQuery || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if ($.fn.DataTable.isDataTable('#sites-table')) {
            destroyTable();
        }

        var dataUrl = tableEl.dataset.datatablesUrl;

        dtInstance = $('#sites-table').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: dataUrl,
                type: 'GET'
            },
            columns: [
                { data: 'name', name: 'name' },
                { data: 'place', name: 'place' },
                { data: 'capacity', name: 'capacity' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 50,
            lengthMenu: [25, 50, 100, 250],
            paging: true,
            pagingType: 'simple_numbers',
            scrollY: '60vh',
            scrollCollapse: true,
            scroller: true,
            deferRender: true,
            language: {
                processing: '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...',
                search: '',
                zeroRecords: 'No matching sites found.',
                info: 'Showing _START_ to _END_ of _TOTAL_ sites',
                infoEmpty: 'No sites found.',
                infoFiltered: '(filtered from _MAX_ total sites)'
            },
            dom: 'rltip'
        });

        var searchInput = document.getElementById('sites-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    if (dtInstance) {
                        dtInstance.search(searchInput.value).draw();
                    }
                }, 250);
            });
        }
    }

    var adminFrame = document.getElementById('admin-content');
    if (adminFrame && !adminFrame._sitesFrameListenerAttached) {
        adminFrame._sitesFrameListenerAttached = true;
        adminFrame.addEventListener('turbo:before-frame-render', destroyTable);
    }
    document.addEventListener('turbo:before-cache', destroyTable);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSitesTable);
    } else {
        initSitesTable();
    }
    document.addEventListener('turbo:load', initSitesTable);
    document.addEventListener('turbo:frame-load', initSitesTable);
}());
</script>
<?php $this->end(); ?>
