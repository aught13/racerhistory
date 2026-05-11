<?php
/**
 * @var \App\View\AppView $this
 * @var int $placeCount
 */

$this->assign('title', 'Places');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Places', 'action' => 'datatables']);
?>
<?php $this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
<?php $this->end(); ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Places</h1>
            <p class="text-muted mb-3">Manage places and locations. <?= (int)$placeCount ?> total.</p>
            <a href="<?= $this->Url->build(['action' => 'add']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-plus-circle"></i> Add New Place
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="places-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="places-search" class="form-control form-control-sm" placeholder="Country, city, or state..." autocomplete="off">
            </div>

            <table
                id="places-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
            >
                <thead class="table-dark">
                    <tr>
                        <th>Country</th>
                        <th>City</th>
                        <th>State</th>
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

    function initPlacesTable() {
        var tableEl = document.getElementById('places-table');
        if (!tableEl || !window.jQuery || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if ($.fn.DataTable.isDataTable('#places-table')) {
            destroyTable();
        }

        var dataUrl = tableEl.dataset.datatablesUrl;

        dtInstance = $('#places-table').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: dataUrl,
                type: 'GET'
            },
            columns: [
                { data: 'country', name: 'country' },
                { data: 'city', name: 'city' },
                { data: 'state', name: 'state' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[1, 'asc']],
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
                zeroRecords: 'No matching places found.',
                info: 'Showing _START_ to _END_ of _TOTAL_ places',
                infoEmpty: 'No places found.',
                infoFiltered: '(filtered from _MAX_ total places)'
            },
            dom: 'rltip'
        });

        var searchInput = document.getElementById('places-search');
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
    if (adminFrame && !adminFrame._placesFrameListenerAttached) {
        adminFrame._placesFrameListenerAttached = true;
        adminFrame.addEventListener('turbo:before-frame-render', destroyTable);
    }
    document.addEventListener('turbo:before-cache', destroyTable);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPlacesTable);
    } else {
        initPlacesTable();
    }
    document.addEventListener('turbo:load', initPlacesTable);
    document.addEventListener('turbo:frame-load', initPlacesTable);
}());
</script>
<?php $this->end(); ?>
