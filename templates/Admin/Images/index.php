<?php
/**
 * @var \App\View\AppView $this
 * @var int $imageCount
 */
$this->assign('title', 'Images');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'datatables']);
?>
<?php $this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
<?php $this->end(); ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Images</h1>
            <p class="text-muted mb-3">
                All Images: <?= (int)$imageCount ?> total.
            </p>
            <a href="<?= $this->Url->build(['action' => 'bulkUploadForm']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-upload"></i> Upload Images
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="images-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="images-search" class="form-control form-control-sm" placeholder="Name, mime, status, id..." autocomplete="off">
            </div>

            <table
                id="images-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
            >
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th style="width: 5rem;">Preview</th>
                        <th>Original Name</th>
                        <th>Mime</th>
                        <th>Size</th>
                        <th>Dimensions</th>
                        <th>Status</th>
                        <th style="width: 8rem;">Actions</th>
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

    function initImagesTable() {
        var tableEl = document.getElementById('images-table');
        if (!tableEl || !window.jQuery || typeof $.fn.DataTable !== 'function') {
            return;
        }

        if ($.fn.DataTable.isDataTable('#images-table')) {
            destroyTable();
        }

        var dataUrl = tableEl.dataset.datatablesUrl;

        dtInstance = $('#images-table').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: dataUrl,
                type: 'GET'
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'preview', name: 'preview', orderable: false, searchable: false },
                { data: 'original_name', name: 'original_name' },
                { data: 'mime', name: 'mime' },
                { data: 'size', name: 'size' },
                { data: 'dimensions', name: 'dimensions' },
                { data: 'status', name: 'status' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            pageLength: 15,
            lengthMenu: [15, 30, 60, 120],
            paging: true,
            pagingType: 'simple_numbers',
            scrollY: '60vh',
            scrollX: true,
            scrollCollapse: true,
            scroller: true,
            deferRender: true,
            language: {
                processing: '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...',
                search: '',
                zeroRecords: 'No matching images found.',
                info: 'Showing _START_ to _END_ of _TOTAL_ images',
                infoEmpty: 'No images found.',
                infoFiltered: '(filtered from _MAX_ total images)'
            },
            dom: 'rltip'
        });

        var searchInput = document.getElementById('images-search');
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
    if (adminFrame && !adminFrame._imagesFrameListenerAttached) {
        adminFrame._imagesFrameListenerAttached = true;
        adminFrame.addEventListener('turbo:before-frame-render', destroyTable);
    }
    document.addEventListener('turbo:before-cache', destroyTable);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImagesTable);
    } else {
        initImagesTable();
    }

    document.addEventListener('turbo:load', initImagesTable);
    document.addEventListener('turbo:frame-load', initImagesTable);
}());
</script>
<?php $this->end(); ?>
